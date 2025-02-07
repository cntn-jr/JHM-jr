<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Progress;

class Entry extends Model
{
    protected $fillable = [
        'student_id','company_id','student_company_id','create_year','create_month','create_day'
    ];
    protected $table = 'entries';

    public function getProgressList()
    {
        return Progress::select('progress.id','student_id','entry_id','action','state','action_date')
                ->join('students', 'progress.student_id', '=', 'students.id')
                ->where('entry_id', $this->id)
                ->orderBy('action_date','asc')
                ->get();
    }

    public function hasProgress()
    {
        $progress = Progress::where('entry_id', $this->id)
                    ->orderBy('action_date','asc')
                    ->first();
        if($progress){
            return true;
        }
        return false;
    }
}
