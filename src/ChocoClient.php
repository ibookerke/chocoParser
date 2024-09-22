<?php

namespace Src;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;
use Throwable;

class ChocoClient
{

    protected string $bearerToken;
    protected Client $client;
    public function __construct(string $bearer)
    {
        $this->bearerToken = $bearer;
        $this->client = new Client();
    }


    /**
     * @throws Exception
     */
    public function getUserdata(): array
    {
        try {
            //    https://api-proxy.choco.kz/api/v3/user/\
            $response = $this->client->get(
                'https://api-proxy.choco.kz/api/v3/user/',
                [
                    'headers' => $this->getHeaders()
                ]
            );

            $statusCode = $response->getStatusCode();
            $response = json_decode($response->getBody(), true); // Decoding JSON response

            if ($statusCode !== 200) {
                throw new Exception("fetching user data error: " . $statusCode);
            }

            return $response['data'];
        } catch (Throwable $e) {
            throw new Exception("fetching user data error: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getTerminals(): array
    {
        try {
            // https://api-proxy.choco.kz/acl/v3/staff/terminals?filter[terminal_types][]=main&filter[terminal_types][]=takeaway&filter[terminal_types][]=promotions&filter[terminal_types][]=waiterless&filter[terminal_types][]=special&filter[terminal_types][]=dr_delivery
            $response = $this->client->get(
                'https://api-proxy.choco.kz/acl/v3/staff/terminals?filter[terminal_types][]=main&filter[terminal_types][]=takeaway&filter[terminal_types][]=promotions&filter[terminal_types][]=waiterless&filter[terminal_types][]=special&filter[terminal_types][]=dr_delivery',
                [
                    'headers' => $this->getHeaders()
                ]
            );

            $statusCode = $response->getStatusCode();
            $response = json_decode($response->getBody(), true); // Decoding JSON response

            if ($statusCode !== 200) {
                throw new Exception("error fetching terminals: " . $statusCode);
            }

            return $response['data'];
        } catch (Throwable $e) {
            throw new Exception("error fetching terminals: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getCustomersData(array $ids): array
    {
        // https://api-proxy.choco.kz/analytics/v1/customers?terminals=9297,9341,9037,9038,9067,9066&page=1&sort=turnover&filter[start_date]=2017-01-01+00:00:00&filter[end_date]=2024-09-22+23:59:59

        $currentDate = date('Y-m-d') . '+23:59:59';
        $terminals = implode(',', $ids);

        $page = 1;
        $limit = 100;
        $result = [];

        try {
            for(;;) {
                // fetching list of customers
                $response = $this->client->get(
                    "https://api-proxy.choco.kz/analytics/v1/customers?terminals={$terminals}&page={$page}&sort=turnover&filter[start_date]=2017-01-01+00:00:00&filter[end_date]={$currentDate}",
                    [
                        'headers' => $this->getHeaders()
                    ]
                );
                $page++;

                $statusCode = $response->getStatusCode();
                $response = json_decode($response->getBody(), true); // Decoding JSON response
                $total = $response['meta']['page']['total'];

                if ($statusCode !== 200) {
                    throw new Exception("error fetching customers data: " . $statusCode);
                }

                $customerListData = [];
                foreach ($response['data'] as $customer) {
                    $customerListData[$customer['id']] = $customer;
                }

                $count = count($result) + count($customerListData);
                echo "fetched $count customers\n";

                sleep(2);

                // https://api-proxy.choco.kz/analytics/v1/customer/12664374?terminals=9297,9341,9037,9038,9067,9066
                foreach($customerListData as $id => $customer) {
                    // get customer details
                    $customerDetails = $this->client->get(
                        "https://api-proxy.choco.kz/analytics/v1/customer/{$customer['id']}?terminals={$terminals}",
                        [
                            'headers' => $this->getHeaders()
                        ]
                    );

                    $customerDetailsData = json_decode($customerDetails->getBody(), true); // Decoding JSON response
                    $customerListData[$id]['details'] = $customerDetailsData['data'];

                    // get payment history
                    // https://api-proxy.choco.kz/analytics/v1/customer/12664374/payment-history?terminals=9297,9341,9037,9038,9067,9066&page=1&filter[start_date]=2024-08-23+00:00:00&filter[end_date]=2024-09-22+23:59:59
                    $paymentHistory = $this->client->get(
                        "https://api-proxy.choco.kz/analytics/v1/customer/{$customer['id']}/payment-history?terminals={$terminals}&page=1&filter[start_date]=2017-01-01+00:00:00&filter[end_date]={$currentDate}",
                        [
                            'headers' => $this->getHeaders()
                        ]
                    );

                    $paymentHistoryData = json_decode($paymentHistory->getBody(), true); // Decoding JSON response
                    $customerListData[$id]['payment_history'] = $paymentHistoryData['data'];
                    usleep(500000);
                }

                $result = array_merge($result, array_values($customerListData));

                if ($count >= $total || $count >= $limit) {
                    break;
                }

                sleep(5);
            }

            return $result;
        } catch (Throwable $ex) {
            throw new Exception("error fetching customers data: " . $ex->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getAllFilialData(array $ids): array
    {
        try {
            $result = [];

            foreach($ids as $id) {
                $filial = $this->getFilialData($id);
                $filial['id'] = $id;
                $result[] = $filial;

                if (count($result) % 20) {
                    sleep(5);
                } else {
                    // sleep for between 1/2s to 1.5s
                    usleep(rand(500000, 1500000));
                }
            }

            return $result;
        } catch (Throwable $e) {
            throw new Exception("error fetching filial data: " . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getFilialData(int $filialId): array
    {
        $today = date('Y-m-d');
        $monthAgo = date('Y-m-d', strtotime('-1 month'));
        try {
            // https://api-proxy.choco.kz/segments/rahmetbiz/main?filial_ids[]=9297&start_date=2024-09-22&end_date=2024-09-22
            $response = $this->client->get(
                "https://api-proxy.choco.kz/segments/rahmetbiz/main?filial_ids[]={$filialId}&start_date={$monthAgo}&end_date={$today}",
                [
                    'headers' => $this->getHeaders()
                ]
            );

            $statusCode = $response->getStatusCode();
            $response = json_decode($response->getBody(), true); // Decoding JSON response

            if ($statusCode !== 200) {
                throw new Exception("error fetching filial data: " . $statusCode);
            }

            return $response['data'];
        } catch (Throwable $e) {
            throw new Exception("error fetching filial data: " . $e->getMessage());
        }
    }


    protected function getHeaders(): array
    {
        return [
            'accept' => 'application/json, text/plain, */*',
            'accept-encoding' => 'gzip, deflate, br, zstd',
            'accept-language' => 'en,en-US;q=0.9,ru;q=0.8',
            'authorization' => 'Bearer ' . $this->bearerToken,
            'dnt' => '1',
            'origin' => 'https://cabinet.rahmet.biz',
            'referer' => 'https://cabinet.rahmet.biz/',
            'sec-ch-ua' => '"Chromium";v="128", "Not;A=Brand";v="24", "Google Chrome";v="128"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"macOS"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'cross-site',
            'sec-gpc' => '1',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
            'x-fingerprint' => Uuid::uuid4()->toString(),
            'x-idempotency-key' => Uuid::uuid4()->toString(),
            'x-language' => 'ru',
        ];
    }



    // doesn't work due to the fact that the second request asks for recaptcha token
    public function authAttempt()
    {
        $config = [
            'base_uri' => 'https://api-proxy.choco.kz'
        ];

        $authData = [
            'phone' => '77083214892',
            'device_id' => '34958380'
        ];

        try {
            // Sending the authorization request (POST request)
            $response = $this->client->get(
                $config['base_uri'] . '/api/v3/user/profiles?filter[phone]=' . $authData['phone']
            );

            // Processing the response
            $statusCode = $response->getStatusCode();

            $firstRequest = json_decode($response->getBody(), true); // Decoding JSON response

            if ($statusCode !== 200) {
                throw new Exception("first request error: " . $statusCode);
            }

            $firstRequestData = $firstRequest['data'][0];
        } catch (Throwable $e) {
            echo "first request error: " . $e->getMessage() . PHP_EOL;
        }

        $secondRequestId = Uuid::uuid4()->toString();
        $secondRequestData = [
            'data' => [
                'attributes' => [
                    'client_id' => $authData['device_id'],
                    'dispatch_type' => "sms",
                    'login' => $authData['phone'],
                    'term_uuids' => []
                ],
                'id' => $secondRequestId,
                'type' => 'code'
            ]
        ];


        try {

            $secondResponse = $this->client->post(
                $config['base_uri'] . '/api/v4/user/code',
                [
                    'json' => $secondRequestData,
                    'headers' => [
                        'X-Fingerprint' => Uuid::uuid4()->toString(),
                        'X-Idempotency-key' => Uuid::uuid4()->toString(),
                        'X-Language' => 'ru',
                        'x-requested-with' => 'XMLHttpRequest'
                    ]
                ]
            );
        } catch (Throwable $ex) {
            echo "second request error: " . $ex->getMessage() . PHP_EOL;
        }

    }
}