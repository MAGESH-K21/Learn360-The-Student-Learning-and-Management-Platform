<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\CollegeBaseController;
use App\Http\Requests\Customer\Registration\AddValidation;
use App\Http\Requests\Customer\Registration\EditValidation;
use App\Models\AlertSetting;
use App\Models\Document;
use App\Models\Note;
use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionHead;
use App\Traits\SmsEmailScope;
use App\Traits\UserScope;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Image, URL;
use ViewHelper;

class CustomerController extends CollegeBaseController
{
    protected $base_route = 'customer';
    protected $view_path = 'customer';
    protected $panel = 'Customer';
    protected $folder_path;
    protected $folder_name = 'customerProfile';
    protected $filter_query = [];
    protected $codeStart = 'MC';

    use SmsEmailScope;
    use UserScope;

    public function __construct()
    {
        $this->folder_path = public_path().DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.$this->folder_name.DIRECTORY_SEPARATOR;
    }

    public function index(Request $request)
    {
        $data = [];
        $data['customer'] = Customer::select('id', 'reg_no', 'name', 'address', 'tel', 'mobile_1', 'mobile_2', 'email', 'extra_info', 'customer_image','status')
            ->where(function ($query) use ($request) {

                if ($request->has('reg_no')) {
                    $query->where('customers.reg_no','like', '%' . $request->reg_no . '%');
                    $this->filter_query['customers.reg_no'] = $request->reg_no;
                }

                if ($request->has('name')) {
                    $query->where('customers.name','like', '%' . $request->name . '%');
                    $this->filter_query['customers.name'] = $request->name;
                }

                if ($request->has('email')) {
                    $query->where('customers.email','like', '%' . $request->email . '%');
                    $this->filter_query['customers.email'] = $request->email;
                }

                if ($request->has('tel')) {
                    $query->where('customers.tel','like', '%' . $request->tel . '%');
                    $this->filter_query['customers.tel'] = $request->tel;
                }

                if ($request->has('mobile')) {
                    $query->where('customers.mobile_1','like', '%' . $request->mobile . '%');
                    $this->filter_query['customers.mobile_1'] = $request->mobile;

                    $query->where('customers.mobile_2','like', '%' . $request->mobile . '%');
                    $this->filter_query['customers.mobile_2'] = $request->mobile;
                }

                if ($request->has('status')) {
                    $query->where('customers.status', $request->status == 'active' ? 1 : 0);
                    $this->filter_query['customers.status'] = $request->get('status');
                }

            })
            ->get();
        $data['url'] = URL::current();
        $data['filter_query'] = $this->filter_query;

        return view(parent::loadDataToView($this->view_path.'.index'), compact('data'));
    }
    
    public function registration()
    {
        $data = [];
        $data['blank_ins'] = new Customer();

        $data['CustomerRegCode'] = $this->randomNum($this->CustomerRegCode,6);

        $customerStatus = CustomerStatus::select('id', 'title')->Active()->pluck('title','id')->toArray();
        $data['customer_status'] = array_prepend($customerStatus,'',0);

        return view(parent::loadDataToView($this->view_path.'.registration.register'), compact('data'));
    }

    public function register(AddValidation $request)
    {
        if ($request->hasFile('customer_main_image')){
            $customer_image = $request->file('customer_main_image');
            $customer_image_name = $request->reg_no.'.'.$customer_image->getClientOriginalExtension();
            $customer_image->move(public_path().DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'customerProfile'.DIRECTORY_SEPARATOR, $customer_image_name);
        }else{
            $customer_image_name = "";
        }

        $request->request->add(['created_by' => auth()->user()->id]);
        $request->request->add(['customer_image' => $customer_image_name]);
        //$request->request->add(['reg_no' => $this->randomNum($this->CustomerRegCode,6)]);

        $customer = Customer::create($request->all());

        if($customer) {
            //Create Customer Ledger
            $request->request->add(['tr_head' => $request->name . ' [' . $customer->reg_no . ']']);
            $request->request->add(['ref_id' => $customer->id]);
            $request->request->add(['acc_id' => $this->customerAccCategory]);
            $trHead = TransactionHead::create($request->all());

            //Manage Opening Balance of Customer
            if ($trHead && $request->amount > 0) {
                if ($request->get('account_type') == "dr_amt") {
                    $drAmount = $request->amount;
                    $crAmount = 0;
                } elseif ($request->get('account_type') == "cr_amt") {
                    $drAmount = 0;
                    $crAmount = $request->amount;
                } else {

                }

                $data = [
                    'date' => Carbon::today(),
                    'tr_head_id' => $trHead->id,
                    'dr_amount' => $drAmount,
                    'cr_amount' => $crAmount,
                    'description' => 'Opening Balance',
                    'created_by' => auth()->user()->id
                ];

                Transaction::create($data);
            }
        }

        /*SMS & Email Alert*/
        $this->registrationConfirm($customer->name,$customer->reg_no,$customer->mobile_1,$customer->email);

        $request->session()->flash($this->message_success, $this->panel. ' Created Successfully. Customer Reg. Code:'.$customer->reg_no);
        return redirect()->route($this->base_route);
    }

    public function view($id)
    {
        $id = decrypt($id);
        $data = [];
        $data['customer'] = Customer::find($id);

        if (!$data['customer']){
            request()->session()->flash($this->message_warning, "Not a Valid Customer");
            return redirect()->route($this->base_route);
        }

        $data['document'] = Document::select('id', 'member_type','member_id', 'title', 'file','description', 'status')
            ->where('member_type','=','customer')
            ->where('member_id','=',$data['customer']->id)
            ->orderBy('created_by','desc')
            ->get();

        $data['note'] = Note::select('created_at', 'id', 'member_type','member_id','subject', 'note', 'status')
            ->where('member_type','=','customer')
            ->where('member_id','=', $data['customer']->id)
            ->orderBy('created_at','desc')
            ->get();

        //transaction
        $data['transactionHead'] = TransactionHead::where(['acc_id' => $this->customerAccCategory, 'ref_id' => $id])->first();

        if($data['transactionHead']) {
            $transaction = $data['transactionHead']->tR()
                ->orderBy('date')
                ->get();

            $adjustment = [];
            $filteredTransaction  = $transaction->filter(function ($value, $key)use($transaction, $adjustment){
                $balance = $value->dr_amount - $value->cr_amount;

                if($key > 0) {
                    $value->balance = $transaction[$key-1]->balance + $balance;
                }else{
                    $value->balance = $value->dr_amount - $value->cr_amount;
                }
                return $value;
            });

            $data['transaction'] = $filteredTransaction;
        }else{

        }

        //login credential
        $data['customer_login'] = User::where([['role_id',10],['hook_id',$data['customer']->id]])->first();

        $data['url'] = URL::current();
        return view(parent::loadDataToView($this->view_path.'.detail.index'), compact('data'));
    }

    public function edit(Request $request, $id)
    {
        $id = decrypt($id);
        $data = [];
        $data['row'] = Customer::find($id);

        if (!$data['row'])
            return parent::invalidRequest();

        $customerStatus = CustomerStatus::select('id', 'title')->Active()->pluck('title','id')->toArray();
        $data['customer_status'] = array_prepend($customerStatus,'Select Status',0);

        //$data['customerInfo'] = $data['row']->customerInfo()->orderBy('sorting_order','asc')->get();

        return view(parent::loadDataToView($this->view_path.'.registration.edit'), compact('data'));
    }

    public function update(EditValidation $request, $id)
    {
        $id = decrypt($id);
        if (!$row = Customer::find($id))
            return parent::invalidRequest();

        if($request->has('reg_no')) {
            $reg_no = $request->reg_no;
            $request->request->remove('reg_no');
        }

        if ($request->hasFile('customer_main_image')) {
            // remove old image from folder
            if (file_exists($this->folder_path.$row->customer_image))
                @unlink($this->folder_path.$row->customer_image);

            /*upload new customer image*/
            $customer_image = $request->file('customer_main_image');
            $customer_image_name = $reg_no.'.'.$customer_image->getClientOriginalExtension();
            $customer_image->move($this->folder_path, $customer_image_name);
        }

        $request->request->add(['updated_by' => auth()->user()->id]);
        $request->request->add(['customer_image' => isset($customer_image_name)?$customer_image_name:$row->customer_image]);

        $customer = $row->update($request->all());

        $request->session()->flash($this->message_success, $this->panel. ' Updated Successfully.');
        return redirect()->route($this->base_route);

    }

    public function delete(Request $request, $id)
    {
        $id = decrypt($id);
        if (!$row = Customer::find($id)) return parent::invalidRequest();

        $row->delete();

        $request->session()->flash($this->message_success, $this->panel.' Deleted Successfully.');
        return redirect()->route($this->base_route);
    }

    public function active(request $request, $id)
    {
        $id = decrypt($id);
        if (!$row = Customer::find($id)) return parent::invalidRequest();

        $request->request->add(['status' => 'active']);

        $row->update($request->all());

        $request->session()->flash($this->message_success, $row->reg_no.' '.$this->panel.' Active Successfully.');
        return redirect()->route($this->base_route);
    }

    public function inActive(request $request, $id)
    {
        $id = decrypt($id);
        if (!$row = Customer::find($id)) return parent::invalidRequest();

        $request->request->add(['status' => 'in-active']);
        $row->update($request->all());

        //in active customer login detail
        $login_detail = User::where([['role_id',5],['hook_id',$row->id]])->first();
        if($login_detail) {
            $request->request->add(['status' => 'in-active']);
            $login_detail->update($request->all());
        }

       $request->session()->flash($this->message_success, $row->reg_no.' '.$this->panel.' In-Active Successfully.');
        return redirect()->route($this->base_route);
    }

    public function bulkAction(Request $request)
    {
        if ($request->has('bulk_action') && in_array($request->get('bulk_action'), ['active', 'in-active', 'delete'])) {

            if ($request->has('chkIds')) {
                foreach ($request->get('chkIds') as $row_id) {
                    $row_id = decrypt($row_id);
                    switch ($request->get('bulk_action')) {
                        case 'active':
                        case 'in-active':
                            $row = Customer::find($row_id);
                            if ($row) {
                                $row->status = $request->get('bulk_action') == 'active'?'active':'in-active';
                                $row->save();
                            }
                            break;
                        case 'delete':
                            $row = Customer::find($row_id);
                            $row->delete();
                            break;
                    }
                }

                if ($request->get('bulk_action') == 'active' || $request->get('bulk_action') == 'in-active')
                    $request->session()->flash($this->message_success, ucfirst($request->get('bulk_action')). ' Action Successfully.');
                else
                    $request->session()->flash($this->message_success, 'Deleted successfully.');

                return redirect()->route($this->base_route);

            } else {
                $request->session()->flash($this->message_warning, 'Please, Check at least one row.');
                return redirect()->route($this->base_route);
            }

        } else return parent::invalidRequest();

    }

    /*bulk import*/
    public function importCustomer()
    {
        return view(parent::loadDataToView($this->view_path.'.registration.import'));
    }

    public function handleImportCustomer(Request $request)
    {
        //file present or not validation
        $validator = Validator::make($request->all(), [
            'file' => 'required'
        ]);
        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator);
        }

        $file = $request->file('file');
        $csvData = file_get_contents($file);
        $rows = array_map("str_getcsv", explode("\n", $csvData));
        $header = array_shift($rows);

        foreach ($rows as $row) {
            if (count($header) != count($row)) {
                continue;
            }

            $row = array_combine($header, $row);

            //Staff validation
            $validator = Validator::make($row, [
                'reg_no'                         => 'unique:customers,reg_no',
                'name'                          => 'required | max:50',
                'address'                       => 'max:100',
                'tel'                           => 'max:15',
                'mobile_1'                      => 'max:15',
                'mobile_2'                      => 'max:15',
                'email'                         => 'max:100 | unique:customers,email'
            ]);

            if ($validator->fails()) {
                return redirect()
                    ->back()
                    ->withErrors($validator);
            }

            //Customer import
            $customer = Customer::create([
                "reg_no"                => $this->randomNum($this->CustomerRegCode,6),
                "name"                  => $row['name'],
                "address"               => $row['address'],
                "tel"                   => $row['tel'],
                "mobile_1"              => $row['mobile_1'],
                "mobile_2"              => $row['mobile_2'],
                "email"                 => $row['email'],
                "extra_info"            => $row['extra_info'],
                'created_by'            => auth()->user()->id
            ]);

            if($customer) {
                //Create Customer Ledger
                $request->request->add(['created_by' => auth()->user()->id]);
                $request->request->add(['tr_head' => $customer->name . ' [' . $customer->reg_no . ']']);
                $request->request->add(['ref_id' => $customer->id]);
                $request->request->add(['acc_id' => $this->customerAccCategory]);
                $trHead = TransactionHead::create($request->all());

                //Manage Opening Balance of Customer
                if ($trHead && $row['dr_amt'] > 0 || $row['cr_amt'] > 0) {
                    $drAmount = isset($row['dr_amt'])?$row['dr_amt']:0;
                    $crAmount = isset($row['cr_amt'])?$row['cr_amt']:0;

                    $data = [
                        'date' => Carbon::today(),
                        'tr_head_id' => $trHead->id,
                        'dr_amount' => $drAmount,
                        'cr_amount' => $crAmount,
                        'description' => 'Opening Balance',
                        'created_by' => auth()->user()->id
                    ];
                    Transaction::create($data);
                }
            }
        }

        $request->session()->flash($this->message_success,'Customers imported Successfully');
        return redirect()->route($this->base_route);
    }

    /*Send Registration Alert*/
    public function registrationConfirm($name,$reg_no,$contactNumbers,$email)
    {
        $alert = AlertSetting::select('sms','email','subject','template')->where('event','=','CustomerRegistration')->first();
        if(!$alert)
            return back()->with($this->message_warning, "Alert Setting not Setup. Contact Admin For More Detail.");

        $subject = $alert->subject;
        $message = str_replace('{name}',$name,$alert->template);
        $message = str_replace('{reg_no}',$reg_no, $message);

        $sms = false;
        $email = false;
        /*Now Send SMS On First Mobile Number*/
        if($alert->sms == 1){
            $contactNumbers = array($contactNumbers);
            $contactNumbers = $this->contactFilter($contactNumbers);
            $smssuccess = $this->sendSMS($contactNumbers,$message);
            $sms = true;
        }

        if($alert->email == 1){
            $emailIds = array($email);
            $emailIds = $this->emailFilter($emailIds);
            $emailSuccess = $this->sendEmail($emailIds, $subject, $message);
            $email = true;
        }

       /* if($sms == true || $email== true) {
            return true;
        }*/
    }

}
