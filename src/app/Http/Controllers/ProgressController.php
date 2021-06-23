<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Entry;
use App\Progress;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if(!($user->is_teacher)){
            return redirect()->route('home');
        }
        // 進捗配列
        $progress_list = DB::select
                        ('SELECT u.attend_num, p.id, p.user_id, p.entry_id, p.action,p.state, p.action_date
                        FROM users u,progress p
                        WHERE u.id = p.user_id AND u.teacher_id = :teacherid
                        ORDER BY u.attend_num ASC, p.entry_id ASC , p.action_date ASC',["teacherid"=> $user->id]);
        // エントリー配列
        $entry_list = DB::select
                    ('SELECT u.attend_num, e.id,e.user_id,e.company_id,c.name
                    FROM entries e, users u, companies c
                    WHERE e.user_id = u.id AND e.company_id = c.id AND u.teacher_id = :teacherid
                    ORDER BY u.attend_num ASC, e.id ASC',["teacherid"=> $user->id]);
        // 生徒配列
        $students = DB::table('users')
                        ->select(['id','name','attend_num'])
                        ->where('teacher_id',$user->id)
                        ->orderBy('attend_num')
                        ->get();
        // 生徒で一番エントリーした人のエントリー数
        $max_entry_count = DB::select ('SELECT MAX(cnt) AS count
                                        FROM (SELECT COUNT(*) cnt FROM entries e,users u
                                        WHERE e.user_id = u.id AND u.teacher_id = :teacherid
                                        GROUP BY e.user_id) num',["teacherid"=> $user->id]);

        // 配列で帰ってくるので変換
        $max_entry_count = $max_entry_count[0]->count;

        // エントリーがクラス全体で0でも1列は作成するため,0の場合1を代入
        if($max_entry_count < 1){
            $max_entry_count = 1;
        }

        // tableタグのwidth値
        // 500 = 一つのエントリーの幅, 100 = 名前幅, 65 = 出席番号幅
        $table_with_px = $max_entry_count * 500 + 100 + 65;

        return view('progress/index')->with([
            'progress_list' => $progress_list,
            'students' => $students,
            'entry_list' => $entry_list,
            'max_entry_count' => $max_entry_count,
            'table_with_px' => $table_with_px,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'action' => ['required','string','regex:/^[会社説明会|試験受験|面接|社長面接]+$/u'],
            'state' => ['required','string','regex:/^[待ち|◯|×|内々定|欠席]+$/u'],
            'action_date' => ['required','date'],
            'company_id' => ['required'],
        ],[
            'state.required' => '状態は必須です。',
            'state.string' => '文字列で入力してください。',
            'state.regex' => '選択欄からお選びください。',
            'action.required' => '活動内容は必須です。',
            'action.string' => '文字列で入力してください。',
            'action.regex' => '選択欄からお選びください。',
            'action_date.required' => '実施日は必須です。',
            'action_date.date' => '日にちを入力してください。',
            'company_id.required' => '会社詳細ページから登録してください。',
        ]);

        $user = Auth::user();
        $company_id = $request->input('company_id');
        $action = $request->input('action');
        $state = $request->input('state');
        $action_date = $request->input('action_date');
        $entry = Entry::
                    where('user_id', $user->id)
                    ->where('company_id', $company_id)
                    ->first();
        $message = '';

        if($user->is_teacher){
            $message = 'あなたは教師なので進捗登録できません。';
            return redirect()->route('companies.show', ['company' => $company_id])->with('status-error',$message);
        }

        if($entry){
            // 会社にエントリーしている場合
            $progress = Progress::
                    where('user_id', $user->id)
                    ->where('entry_id', $entry->id)->get();
            if(!($progress) || $progress->count() < 5){
                // 同じ進捗が登録されていない場合
                Progress::create([
                    'user_id' => $user->id,
                    'entry_id' => $entry->id,
                    'action' => $action,
                    'state' => $state,
                    'action_date' => $action_date,
                ]);
                return redirect()->route('companies.show', ['company' => $company_id])->with('status','進捗を登録しました。');
            }else{
                $message = "進捗は5件までしか登録することができません。";
                return redirect()->route('companies.show', ['company' => $company_id])->with('status-error',$message);
            }
        }else{
            return redirect()->route('companies.show', ['company' => $company_id])->with('status-error','エントリーしていないので進捗を登録できません。');
        }
    }

    public function update(Request $request , $progress_id)
    {
        $request->validate([
            'state' => ['required','string','regex:/^[待ち|◯|×|内々定|欠席]+$/u'],
            'action_date' => ['required','date'],
            'company_id' => ['required','integer'],
        ],[
            'state.required' => '状態は必須です。',
            'state.string' => '文字列で入力してください。',
            'state.regex' => '選択欄からお選びください。',
            'action_date.required' => '実施日は必須です。',
            'action_date.date' => '日にちを入力してください。',
            'company_id.required' => '会社詳細ページから変更してください。',
            'company_id.integer' => '会社IDが不正です。',
        ]);

        $user = Auth::user();
        $company_id = $request->input('company_id');
        $state = $request->input('state');
        $action_date = $request->input('action_date');
        $entry = Entry::
                    where('user_id', $user->id)
                    ->where('company_id', $company_id)
                    ->first();
        $message = '';

        if($user->is_teacher){
            $message = 'あなたは教師なのでこの処理はできません。';
            return redirect()->route('companies.show', ['company' => $company_id])->with('status-error',$message);
        }

        if($entry){
            // 会社にエントリーしている場合
            $progress = Progress::where('id', $progress_id)
                        ->where('user_id', $user->id)
                        ->first();
            if($progress){
                // 進捗が登録されている場合update
                $progress->state = $state;
                $progress->action_date = $action_date;
                $progress->save();
                return redirect()->route('companies.show', ['company' => $company_id])->with('status','進捗を変更しました。');
            }else{
                $message = "進捗が登録されていないのでこの処理はできません。";
                return redirect()->route('companies.show', ['company' => $company_id])->with('status-error',$message);
            }
        }else{
            return redirect()->route('companies.show', ['company' => $company_id])->with('status-error','エントリーしていないのでこの処理はできません。');
        }
    }

    public function destroy($progress_id)
    {
        $user = Auth::user();
        $progress = Progress::find($progress_id);
        if($progress){
            if($user->id != $progress->user_id){
                // 自分の進捗IDではない場合
                return redirect()->back()->with('status-error','他人の進捗は削除できません。');
            }
            Progress::destroy($progress->id);
            return redirect()->back()->with('status','進捗（'.$progress->action.'）を削除しました。');
        }else{
            return redirect()->back()->with('status-error','進捗の削除処理に失敗しました。');
        }
    }
}