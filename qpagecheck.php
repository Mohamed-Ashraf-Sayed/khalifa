$kernel = app(Illuminate\Contracts\Http\Kernel::class);
$admin = App\Models\User::where('email','admin@alqarwana.com')->first();
$proj = App\Models\Project::first(); $asset = App\Models\Asset::first();
$lg = App\Models\LetterOfGuarantee::first(); $ten = App\Models\Tender::first();
$quo = App\Models\Quotation::first(); $mr = App\Models\MaterialRequisition::first();
$dsr = App\Models\DailySiteReport::first();
$urls = ['/dashboard','/letters-of-guarantee','/letters-of-guarantee/create',"/letters-of-guarantee/{$lg->id}",'/insurance-policies','/insurance-policies/create','/tenders','/tenders/create',"/tenders/{$ten->id}",'/quotations','/quotations/create',"/quotations/{$quo->id}",'/daily-site-reports','/daily-site-reports/create',"/daily-site-reports/{$dsr->id}",'/labor-attendances','/labor-attendances/create','/equipment-logs','/material-requisitions','/material-requisitions/create',"/material-requisitions/{$mr->id}","/projects/{$proj->id}","/assets/{$asset->id}",'/reports'];
$fail=0;
foreach($urls as $u){
  try { $req = Illuminate\Http\Request::create($u,'GET'); $req->setLaravelSession(app('session')->driver());
    Illuminate\Support\Facades\Auth::login($admin);
    $res = $kernel->handle($req); $code=$res->getStatusCode();
    if($code!==200){ echo "FAIL $code  $u\n"; $fail++; }
  } catch (\Throwable $e){ echo "ERR  $u :: ".$e->getMessage()."\n"; $fail++; }
}
echo $fail===0 ? "ALL PAGES 200 (".count($urls)." pages)\n" : "FAILURES: $fail\n";
