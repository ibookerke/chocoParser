<?php

use Src\Services\FetchCustomersDataService;
use Src\Services\FetchMainService;
use Src\Services\FetchServiceInterface;

require 'vendor/autoload.php';

// TODO: add refresh token reAuth in future:
// code - refreshToken, which is in Cookies
// payload:
/*
    code: def5020077d0da29c3a133c8e899730e33f729ec68975e6c20f272d9d7bc4426dcf9df0d50aa78f22d5cdbc98e00ea52d5caf39eab4b005166b087a54ea1c4a5fb5a2230bf999e16316aa35f9a885bbd48b51d7073a266245cc8cff1f5782ca7ac1e1ab8f4c3bcbfa3bf5e4d6bd6ea60ca10571097d03cf8844112abd7d587c35497c5e34f5d16905342241fb84ea13aad71d56dce8dce1bdd60c3a55555629ddd2ff6b7d5ec70b156acea5569518865d1bbc3d0e62dd3951f4851f4a17f87d6a5f3af865defbfbafc819cea4ecf2f6d6e604a5c07476b80edd389950cd49410c4f8b671ec388b852a4d0036fb8109db5c824bf0765d34012d8d822f07d80e54dbadbfe31a20c26d098ee46fc32987c08266a77b0188dee41c5e2971fb8ec65ce2b97ff915a3a5bf341e849ec288e60fb683999b6d4d4a50f7a13d7d9b2974ec878b297cc34a5bfde34edf0d2d0cc94348db80b7fad91e0ae795453df708bb166a888caff92ccf238f5e92c3222c71ffd2dddd
    grant_type: authorization_code
    client_id: 34958380
    redirect_uri: https://cabinet.rahmet.biz/user/auth
 */

// response:
/*
 * {
    "token_type": "Bearer",
    "expires_in": 3600,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6ImNob2NvIn0.eyJhdWQiOiIzNDk1ODM4MCIsImp0aSI6ImNkNjEzYzU0MDkwOTc2ZWU3YWI2YzEyNjZjYjgyOWFkZWJiOTc1YjgyY2UwYmE3NjQwMzNjN2ZhMTVlYjU3MzUzZjRhMjViZDhmOTkzNzY4IiwiaWF0IjoxNzI3MDIzODMzLjk2MTIwNiwibmJmIjoxNzI3MDIzODMzLjk2MTIwNiwiZXhwIjoxNzI3MDI3NDMzLjkyNDEyNSwic3ViIjoiMTMwNzg4ODEiLCJzY29wZXMiOlsidXNlcl9wcm9maWxlIiwiZ2F0ZXdheV9hdXRoIiwicmFobWV0X2J1c2luZXNzIiwib2F1dGgyIiwib2F1dGgyX2F1dGhjb2RlIiwiY2hvY29saWZlX3JhaG1ldCJdLCJzY29wZXNfc3RyIjoidXNlcl9wcm9maWxlIGdhdGV3YXlfYXV0aCByYWhtZXRfYnVzaW5lc3Mgb2F1dGgyIG9hdXRoMl9hdXRoY29kZSBjaG9jb2xpZmVfcmFobWV0Iiwib3duZXJfaWQiOjExNTU3NTExfQ.VyPNSrmautbTQ7tU0odd2vS-LgOsCZYVFswxeUGo-qpb8r3m_habCJdW3uEqpz1TMbWHr6VzxeZM4N7gQlUx8xVQpW99dU8PAxN51oa0q_0-JOPjuzKCacrdOHEtBchEQr0Q4GHOqrQt5eqhpPrHlkNIozQEygpVsdF3gSn_nC_zwYH-o4dbntnUHTOdfs1Ec_HJ0yC8bckacBQZpCY43QcwrKhSFPgUyrbWM5yqsFPsSG0DOqCMkkyH3qXBoS4DWUUFXc3Jj7QYZzR_gOuugfeeFJrdSi9QyPSHwZ0cQd2m-HEh12OJMQ0nR1c_B6w_svFfLLHKqia-jqwyje91yw",
    "refresh_token": "def50200e58bc4ce92331db1c5b17e1c19fc574fdb78ad93ce7ff25749cce8a10b5e6194e217e492b6d04a853bf6ad7f585d5c4ffbec8f0a6d95bc0168500c37fe51edad37c631384c70f0e9414b51390c5de3dece6b227b991733306d4132ec64ed19dd83e2ae64a65e5c574bde9b7c7a7e7730d93ea902dda29bbef71156b74e68374011af52ef7b673fcfad931839b7088c6a4683128fba1f77c7f40ad35193a3b5b737aaaac5e7e6ac2e0bb191d0da4fe346fac1a97ace5ee302ea3bbbbe7b4f32d354e9bf1964825e802c55ce80d623bcf77970ae3b14a6ebc4a161fbb4113734c2933d9452417a3e1a3c1ec8689128bae956d3bc6db22954fdb39d02db782bba54e111e939a7fdf3ac30c39e113718a67a0120ac2b43da3c7343a4cb82e351e4c841abf51bc06d2175f56ad3af7438339d8452400f49cebf26b195ae92de00408ccdad7050e950ca93d6fbd08b61da36ee314f568fabd34d33e3073353c4ce0497330ad9414047dc6bbab71456a12893f39e3dc4f217a1d15be4d510cae0e5d51375fbc89e918ff2a67a8ba1fcf72de5ee4bcdd441e9fd38e090b403ef1a85b8fe70f1b9835eb0ab47437ddc4a495d74d5774a6422a0cf6664111c64da3382f13c9a99ce1288c503ef"
}
 */

$args = $argv;
$startTime = microtime(true);

if (count($args) < 2) {
    echo "Incorrect number of parameters: you should be calling: `php index.php <action> <token>` \n";
    echo "allowed actions: 1: main, 2: customers \n";
    exit(1);
}

$action = $args[1];
$token = $args[2];

/** @var FetchServiceInterface $handler */
$handler = match ($action) {
    'main' => new FetchMainService($token),
    'customers' => new FetchCustomersDataService($token),
    default => null,
};

if ($handler === null) {
    echo "Invalid action: $action \n";
    exit(1);
}

$handler->handle();

$endTime = microtime(true);
$executionTime = ($endTime - $startTime);

echo "Execution time: $executionTime seconds \n";

