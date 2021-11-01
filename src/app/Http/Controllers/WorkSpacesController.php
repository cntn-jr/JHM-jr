<?php

namespace App\Http\Controllers;

use App\WorkSpaces;
use App\Students;
use App\Membership;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Rules\WorkSpaceYear;


class WorkSpacesController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:web']);
    }

    public function create(){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        $now = Carbon::now();
        $this_year = $now->year;
        $years = [];
        for($i = -3; $i < 4; $i++){
            array_push($years, $this_year - $i);
        }
        return view('workspaces.create')->with(['years' => $years, 'this_year' => $this_year]);
    }

    public function store(Request $request){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        $request->validate([
            'name' => ['required', 'string', 'max:31'],
            'year' => ['required', new WorkSpaceYear],
        ],[
            'name.max' => '名前は31文字までです'
        ]);
        $workspace = WorkSpaces::create([
            'teacher_id' => $login_user->id,
            'class_name' => $request->input('name'),
            'year' => $request->input('year'),
        ]);
        Cookie::queue('workspace_id', $workspace->id, 1000000);
        //Vueを取り入れた後に消す
        $request->session()->put('workspace_id', $workspace->id);
        return redirect()->route('companies.index');
    }

    public function edit($id, Request $request){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        //ワークスペース作成者でなければ、アクセスさせない
        $workspace = WorkSpaces::find($id);
        if($login_user->id != $workspace->teacher_id)
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        $now = Carbon::now();
        $this_year = $now->year;
        $years = [];
        for($i = -3; $i < 4; $i++){
            array_push($years, $this_year - $i);
        }
        return view('workspaces.edit')->with(['workspace' => $workspace, 'years' => $years, 'this_year' => $this_year]);
    }

    public function update($id, Request $request){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('progress.index')->with('status-error', 'アクセス権限がありません');
        //ワークスペース作成者でなければ、アクセスさせない
        $workspace = WorkSpaces::find($id);
        if($login_user->id != $workspace->teacher_id)
            return redirect()->route('progress.index')->with('status-error', 'アクセス権限がありません');
        $request->validate([
            'class_name' => ['required', 'string', 'max:31'],
            'year' => ['required', new WorkSpaceYear],
        ],[
            'name.max' => '名前は31文字までです'
        ]);
        $workspace->class_name = $request->input('class_name');
        $workspace->year = $request->input('year');
        $workspace->save();
        return redirect()->route('progress.index')->with('status', 'ワークスペースの情報を更新しました');
    }

    public function destroy($id){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('progress.index')->with('status-error', 'アクセス権限がありません');
        //ワークスペース作成者でなければ、アクセスさせない
        $workspace = WorkSpaces::find($id);
        if($login_user->id != $workspace->teacher_id)
            return redirect()->route('progress.index')->with('status-error', 'アクセス権限がありません');
        $workspace->delete();
        Cookie::queue('workspace_id', null, 1000000);
        return redirect()->route('progress.index')->with('status', 'ワークスペースの削除に成功しました');
    }

    public function change($id, Request $request){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        //ワークスペース作成者でなければ、アクセスさせない
        $workspace = WorkSpaces::find($id);
        if($login_user->id != $workspace->teacher_id)
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        Cookie::queue('workspace_id', $id, 1000000);
        //Vueを取り入れた後に消す
        $request->session()->put('workspace_id', $id);
        return back();
    }

    public function showMember(){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        $workspace_id = Cookie::get('workspace_id');
        $workspace = WorkSpaces::find($workspace_id);
        //ワークスペース作成者でなければ、アクセスさせない
        if($login_user->id != $workspace->teacher_id)
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        $member = $workspace->getMember();
        return view('workspaces.showMember')->with(['member' => $member]);
    }

    public function addStudentsShow(){
        $login_user = Auth::user();
        if(!$login_user->is_teacher())
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');
        $workspace_id = Cookie::get('workspace_id');

        $students = Students::all();
        $added_students_id = \DB::table('membership')->select('student_id')->where('workspace_id',$workspace_id)->pluck('student_id');


        return view('workspaces.addStudentsShow')->with(['students' => $students, 'added_students_id' => $added_students_id]);
    }

    public function addStudents(Request $request){
        $login_user = Auth::user();
        $workspace_id = Cookie::get('workspace_id');
        if(!$login_user->is_teacher())
            return redirect()->route('companies.index')->with('status-error', 'アクセス権限がありません');

        $added_students_id = \DB::table('membership')->select('student_id')->where('workspace_id',$workspace_id)->pluck('student_id');

        foreach ($request->students as $student_id) {
            $judg = 0;
            foreach ($added_students_id as $added_student_id) {
                if ($student_id == $added_student_id) {
                    $judg = 1;
                }
            }

            if ($judg == 0) {
                membership::create([
                    'workspace_id' => $workspace_id,
                    'student_id' => $student_id,
                ]);
            }
        }

        return redirect()->route('workspaces.showMember');
    }
}
