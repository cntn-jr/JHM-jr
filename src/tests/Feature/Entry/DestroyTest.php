<?php

namespace Tests\Feature\Entry;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\User;
use App\Entry;
use App\Company;
use App\Students;

class DestroyTest extends TestCase
{
    /**
     * @test
     */

    use RefreshDatabase;

    public function test_dummy()
    {
        $this->markTestSkipped('ダミースキップ');
        $this->assertTrue(true);
    }

    // public function deleteByTeacher(){
    //     $teacher = User::first();
    //     $company = Company::first();
    //     $response = $this
    //                 ->actingAs($teacher)
    //                 ->get('companies/'.$company->id);
    //     // $response->assertStatus(200);

    //     $entry = Entry::first();
    //     $response = $this->delete(route('entries.destroy', ['entry' => $entry->id]));
    //     $response->assertStatus(302);
    //     $response->assertRedirect('/home');
    //     $response->assertSessionHas("status-error", "あなたは教師なのでこの処理はできません。");
    // }

    // public function deleteByEnteredStudent(){
    //     $student = Students::first();
    //     $entry = Entry::where('user_id', $student->id)
    //                 ->first();
    //     $enteredCompany = Company::find($entry->company_id)
    //                 ->first();
    //     $response = $this
    //                 ->actingAs($student)
    //                 ->get('companies/'.$enteredCompany->id);
    //     $response->assertStatus(200);

    //     $response = $this
    //                 ->delete(route( 'entries.destroy', ['entry' => $entry->id] ));
    //     $response->assertStatus(302);
    //     $response->assertRedirect('/companies');
    // }

    // public function deleteByNotEnteredStudent(){
    //     $student = Students::first();
    //     $notEntry = Entry::where('user_id', '<>', $student->id)
    //                 ->first();
    //     $notEnteredCompany = Company::where('id', $notEntry->company_id)
    //                 ->first();
    //     $response = $this
    //                 ->actingAs($student)
    //                 ->get('companies/'.$notEnteredCompany->id);
    //     $response->assertStatus(200);

    //     $response = $this
    //                 ->delete(route( 'entries.destroy', ['entry' => $notEntry->id] ));
    //     $response->assertStatus(302);
    //     $response->assertRedirect('/companies');
    //     $response->assertSessionHas("status-error", "あなたはエントリーしていないのでこの処理はできません。");
    // }
}
