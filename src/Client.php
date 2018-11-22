<?php
namespace BingTest;

use Microsoft\BingAds\Auth\ApiEnvironment;
use Microsoft\BingAds\Auth\Authentication;
use Microsoft\BingAds\Auth\AuthorizationData;
use Microsoft\BingAds\Auth\OAuthDesktopMobileAuthCodeGrant;
use Microsoft\BingAds\Auth\OAuthWithAuthorizationCode;
use Microsoft\BingAds\Auth\ServiceClient;
use Microsoft\BingAds\Auth\ServiceClientType;
use Microsoft\BingAds\V12\CampaignManagement\Ad;
use Microsoft\BingAds\V12\CampaignManagement\AdGroup;
use Microsoft\BingAds\V12\CampaignManagement\AdType;
use Microsoft\BingAds\V12\CampaignManagement\Campaign;
use Microsoft\BingAds\V12\CampaignManagement\CampaignType;
use Microsoft\BingAds\V12\CampaignManagement\GetAdGroupsByCampaignIdRequest;
use Microsoft\BingAds\V12\CampaignManagement\GetAdsByAdGroupIdRequest;
use Microsoft\BingAds\V12\CampaignManagement\GetAdsByAdGroupIdResponse;
use Microsoft\BingAds\V12\CampaignManagement\GetCampaignsByAccountIdRequest;
use Microsoft\BingAds\V12\CustomerManagement\AdvertiserAccount;
use Microsoft\BingAds\V12\CustomerManagement\GetUserRequest;
use Microsoft\BingAds\V12\CustomerManagement\Paging;
use Microsoft\BingAds\V12\CustomerManagement\Predicate;
use Microsoft\BingAds\V12\CustomerManagement\PredicateOperator;
use Microsoft\BingAds\V12\CustomerManagement\SearchAccountsRequest;
use Microsoft\BingAds\V12\CustomerManagement\SearchAccountsResponse;

require_once(__DIR__ . '/parameters.php');

class Client {
    const PAGE_MAX_SIZE = 1000;

    /**
     * @var ServiceClient
     */
    public $serviceClient;

    public function __construct($serviceClientType, $customAccountId = '')
    {
        if (empty($serviceClientType)) {
            throw new \Exception('ServiceClientType must be passed');
        }
        $this->setServiceClient($serviceClientType, $customAccountId);
    }

	public function setServiceClient($serviceClientType, $customAccountId = '')
    {
        $developerToken = DEVELOPER_TOKEN;
        $clientSecret   = CLIENT_SECRET;
        $clientId       = CLIENT_ID;
        $customerId     = CUSTOMER_ID;
        $accountId      = $customAccountId ?: ACCOUNT_ID;
        $refreshToken = REFRESH_TOKEN;
        $redirectUri  = REDIRECT_URI;

        $apiEnvironment = ApiEnvironment::Production;

        /** @var OAuthWithAuthorizationCode|Authentication $authentication */
        $authentication = (new OAuthDesktopMobileAuthCodeGrant())
            ->withClientId($clientId)
            ->withClientSecret($clientSecret)
            ->withRefreshToken($refreshToken)
            ->withEnvironment($apiEnvironment)
            ->withRedirectUri($redirectUri);

        $authentication->RequestOAuthTokensByRefreshToken($refreshToken);

        $authorizationData = (new AuthorizationData())
            ->withAuthentication($authentication)
            ->withCustomerId($customerId)
            ->withAccountId($accountId)
            ->withDeveloperToken($developerToken);

        $serviceClient = new ServiceClient($serviceClientType, $authorizationData, $apiEnvironment);

        if ($serviceClient instanceof ServiceClient) {
            $this->serviceClient = $serviceClient;
            return true;
        } else {
            throw new \Exception('Unable to create service client');
        }
    }

    /**
     * @param bool|null $active - filter by status
     * @return AdvertiserAccount[]
     */
    public function getAllAccounts($active = null)
    {
        $request = new SearchAccountsRequest;

        $predicate = new Predicate();
        $predicate->Field = 'UserId';
        $predicate->Operator = PredicateOperator::Equals;
        $predicate->Value = $this->getUserId();

        $predicates[] = $predicate;

        if ($active !== null) {
            $active = (boolean)$active;
            $predicate = new Predicate;
            $predicate->Field = 'AccountLifeCycleStatus';
            $predicate->Operator = 'Equals';
            $predicate->Value = $active ? 'Active' : 'Inactive';

            $predicates[] = $predicate;
        }

        $accounts = [];
        $pageIndex = 0;
        $foundLastPage = false;

        while (!$foundLastPage) {
            $paging = new Paging();
            $paging->Index = $pageIndex++;
            $paging->Size = self::PAGE_MAX_SIZE;

            $request->PageInfo = $paging;
            $request->Predicates = $predicates;

            /** @var SearchAccountsResponse $response */
            $response = $this->serviceClient->GetService()->SearchAccounts($request);
            $pageAdvertiserAccounts = $response->Accounts->AdvertiserAccount;
            $accounts = array_merge($accounts, $pageAdvertiserAccounts);
            $foundLastPage = self::PAGE_MAX_SIZE > count($pageAdvertiserAccounts);
        }

        return $accounts;
    }

    /**
     * Returns ID of the current user
     * @return int
     */
    public function getUserId()
    {
        $request = new GetUserRequest();
        $user = $this->serviceClient->GetService()->GetUser($request)->User;
        return $user->Id;
    }

    /**
     * @param string $accountId
     * @return Campaign[]
     */
    public function getCampaignsByAccountId($accountId)
    {
        $request               = new GetCampaignsByAccountIdRequest();
        $request->AccountId    = $accountId;

        $response = $this->serviceClient->GetService()->GetCampaignsByAccountId($request)->Campaigns->Campaign;

        return $response ?? [];
    }

    /**
     * Returns array of all Adgroups for given campaign
     * @param  string $campaignId
     * @return AdGroup[]
     */
    public function getAdgroupsByCampaignId($campaignId)
    {
        $request             = new GetAdGroupsByCampaignIdRequest();
        $request->CampaignId = $campaignId;

        $response = $this->serviceClient->GetService()->GetAdGroupsByCampaignId($request);
        return $response->AdGroups->AdGroup ?? [];
    }

    /**
     * @param string $adgroupId
     * @return Ad[]
     */
    public function getAdgroupAds($adgroupId)
    {
        $request = new GetAdsByAdGroupIdRequest();
        $request->AdGroupId = $adgroupId;
        // All possible ad types
        $request->AdTypes = [
            AdType::Text,
            AdType::Image,
            AdType::Product,
            AdType::AppInstall,
            AdType::ExpandedText,
            AdType::DynamicSearch,
        ];
        /** @var GetAdsByAdGroupIdResponse $response */
        $response = $this->serviceClient->GetService()->GetAdsByAdGroupId($request);

        return $response->Ads->Ad ?? [];
    }
}