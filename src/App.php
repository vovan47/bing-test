<?php
namespace BingTest;

use BingTest\Client as ApiClient;
use Microsoft\BingAds\Auth\ServiceClientType;

class App {

    public $out;

	public function __construct()
    {
        $out = fopen("php://stdout", "w");
        $this->out = $out;
        // Disable WSDL caching.
        ini_set("soap.wsdl_cache_enabled", "0");
        ini_set("soap.wsdl_cache_ttl", "0");
        $this->log('App initialized.');
    }

    public function log($msg)
    {
        $msg = '[' . date('d-m-Y H:i:s') . '] - ' . $msg;
        $msg .= ' - MEM:' . Util::getMemoryUsage() . PHP_EOL;
        fwrite($this->out, $msg);
        fflush($this->out);
    }

    public function run() {
        $masterAccountClient = new ApiClient(ServiceClientType::CustomerManagementVersion12);
        $this->log("Fetching account list...");
        $clientAccounts = $masterAccountClient->getAllAccounts();
        $count = count($clientAccounts);
        $this->log(sprintf("Found %d accounts", $count));
        $makeApiCalls = true;
        foreach ($clientAccounts as $key => $customer) {
            try {
                $accountId = $customer->Id;
                $name = $customer->Name;
                $campaignApiClient = new ApiClient(ServiceClientType::CampaignManagementVersion12, $accountId);
                $this->log(sprintf("Created Apiclient #%d, ID = %d", ($key+1), $accountId));

                if ($key > 50) {
                    $makeApiCalls = true;
                }

                if ($makeApiCalls) {
                    $remoteCampaigns = $campaignApiClient->getCampaignsByAccountId($accountId);    
                
                    $this->log(
                        sprintf(
                            "[%d/%d] Account '%s' [%s] has %d campaign(s)",
                            ($key+1),
                            $count,
                            $name,
                            $accountId,
                            count($remoteCampaigns))
                    );
                    foreach ($remoteCampaigns as $campaign) {
                        $campaignId = $campaign->Id;
                        $campaignName = $campaign->Name;
                        $adgroups = $campaignApiClient->getAdgroupsByCampaignId($campaignId);
                        $this->log(sprintf("\tCampaign '%s' has %d adgroup(s)", $campaignName, count($adgroups)));

                        foreach ($adgroups as $adgroup) {
                            $adgroupId = $adgroup->Id;
                            $adgroupName = $adgroup->Name;
                            if ($makeApiCalls) {
                                $ads = $campaignApiClient->getAdgroupAds($adgroupId);
                            }
                            $this->log(sprintf("\t\tAdgroup '%s' has %d ad(s)", $adgroupName, count($ads)));
                        }
                    }
                }
            } catch (\Exception $e) {
                $errorString = "Exception '" . get_class($e) . "' with message '" . $e->getMessage();
                $this->log($errorString);
                var_dump($e->getTraceAsString());
            } finally {
                $campaignApiClient = null;
                gc_collect_cycles();
            }
        }
        $this->log('Done');
        return true;
    }

}