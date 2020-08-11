<?php
namespace App\Exports;

use App\Models\AdminLog;
use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class FinanceExport implements FromCollection, WithHeadings {

    protected $where;
    public function __construct($invoices){
        $this->where = $invoices;
    }
    public function collection() {
        $data = $this->where;
        $total = Order::select('ld_school.name','ld_student.real_name','ld_student.phone','ld_order.price','ld_order.lession_price','ld_order.class_id','ld_order.nature')
            ->leftJoin('ld_school','ld_school.id','=','ld_order.school_id')
            ->leftJoin('ld_student','ld_student.id','=','ld_order.student_id')
            ->where(function($query) use ($data) {
                if(isset($data['start_time']) && !empty($data['start_time'] != ''&&$data['start_time'] != 0 )){
                    $query->where('ld_order.create_time','>=',$data['start_time']);
                }
                if(isset($data['end_time']) && !empty($data['end_time'] != ''&&$data['end_time'] != 0 )){
                    $query->where('ld_order.create_time','<=',$data['end_time']);
                }
                $query->where('ld_order.status','=',1)
                    ->orwhere('ld_order.status','=',2);
            })
            ->get();
        foreach ($total as $k=>&$v){
            if($v['nature'] == 1){
                $lesson = CourseSchool::where(['id'=>$v['class_id']])->first();
            }else{
                $lesson = Coures::where(['id'=>$v['class_id']])->first();
            }
            $subject = Subject::where(['id'=>$lesson['parent_id']])->first();
            $v['class_name'] = $lesson['title'];
            $v['subject_name'] = $subject['subject_name'];
            unset($v['class_id']);
            unset($v['nature']);
        }
        return $total;
    }

    public function headings(): array{
        return [
            '分校名称',
            '姓名',
            '手机号',
            '课程名称',
            '所属学科',
            '课程价格',
            '购买价格'
        ];
    }
}
