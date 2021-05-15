<?php
error_reporting(0);
function Indodax($key, $secretKey, $data)
{
    $url = 'https://indodax.com/tapi';

    $post_data = http_build_query($data, '', '&');
    $sign = hash_hmac('sha512', $post_data, $secretKey);

    $headers = ['Key:' . $key, 'Sign:' . $sign];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_RETURNTRANSFER => true
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

function get_rate($id)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://indodax.com/api/ticker/' . $id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return json_decode($response, true);
}
function get_string_between($string, $start, $end)
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

// Please find Key from trade API Indodax exchange
$key = "";
// Please find Secret Key from trade API Indodax exchange
$secretKey = "";
$data = [
    'method' => 'getInfo',
    'timestamp' => '1578304294000',
    'recvWindow' => '1578303937000'
];
$idx = Indodax($key, $secretKey, $data);
$saldo = number_format($idx['return']['balance']['idr'], 0, ',', '.');
echo "Halo, {$idx['return']['name']}. Selamat datang di BOT Indodax\n";
echo "Saldo IDR {$saldo}\n";
echo "Masukan coin trade hari ini : ";
$coin = strtolower(trim(fgets(STDIN)));
$rate_now = get_rate($coin . "idr");
if ($rate_now['ticker']) {
    echo "HARI INI " . strtoupper($coin) . " - IDR\n";
    $high = number_format($rate_now['ticker']['high'], 0, ',', '.');
    $low = number_format($rate_now['ticker']['low'], 0, ',', '.');
    $last = number_format($rate_now['ticker']['last'], 0, ',', '.');
    echo "HIGH : Rp.{$high} || LOW : Rp.{$low} || LAST : Rp.{$last}\n";
    echo "Masukan Nominal Trade : ";
    $dipakai = (int)trim(fgets(STDIN));
    echo "Presentase Profit (ex 0.75) : ";
    $win = trim(fgets(STDIN));
    echo "Presentase Lose (ex 0.75) : ";
    $lose = trim(fgets(STDIN));
    $c0rz = get_rate($coin . "idr");
    $last_price_bgt = $c0rz['ticker']['last'];
    $data = [
        'method' => 'trade',
        'timestamp' => '1578304294000',
        'recvWindow' => '1578303937000',
        'pair' => $coin . '_idr',
        'type' => 'buy',
        'price' => $last_price_bgt,
        'idr' => (int)$dipakai,
        'btc' => '',
    ];
    $buy = Indodax($key, $secretKey, $data);
    $target_untung = $c0rz['ticker']['sell'] + ($c0rz['ticker']['sell'] * $win / 100);
    $target_rugi = $c0rz['ticker']['sell'] - ($c0rz['ticker']['sell'] * $lose / 100);
    $price_untung = number_format((int)$target_untung, 0, ',', '.');
    $price_rugi = number_format((int)$target_rugi, 0, ',', '.');
    if ($buy['success'] == TRUE) {
        $ngantri = 1;
        $infohis = [
            'method' => 'orderHistory',
            'timestamp' => '1578304294000',
            'recvWindow' => '1578303937000',
            'pair' => $coin . '_idr',
            'count' => '1',
            'from' => ''
        ];
        sleep(2);
        while ($ngantri < 2) {
            $pending = Indodax($key, $secretKey, $infohis);
            if ($pending['return']['orders'][0]["status"] != "filled") {
                echo "Dalam tahap membeli coin. [ORDER BOOK : " . $pending['return']['orders'][0]["order_id"] . "]\n";
                sleep(3);
            } else {
                $ngantri = 99;
            }
        }
        echo "Beli di Harga : " . number_format($last_price_bgt, 0, ',', '.') . " fee : " . $buy['return']['fee'] . " || Target Untung : Rp." . $price_untung . " || Target Rugi Rp." . $price_rugi . "\n";
        $duit_lu_bersih = $dipakai - $fee;
        while (True) {
            $rate_now = get_rate($coin . "idr");
            // $temp = ceil(($coin_didapat * $rate_now['ticker']['last'])) / $duit_lu_bersih;
            // $temp = round($temp, 5);
            $baru = number_format($rate_now['ticker']['last'], 0, ',', '.');
            echo "Harga Saat ini : Rp.{$baru}\n";
            echo "DELAY . . . .\n\n";
            sleep(5);
            if ($rate_now['ticker']['last'] >= $target_untung) {
                $data = [
                    'method' => 'getInfo',
                    'timestamp' => '1578304294000',
                    'recvWindow' => '1578303937000'
                ];
                $prof = Indodax($key, $secretKey, $data);
                $coin_didapat = $prof["return"]["balance"][$coin];
                $data = [
                    'method' => 'trade',
                    'timestamp' => '1578304294000',
                    'recvWindow' => '1578303937000',
                    'pair' => $coin . '_idr',
                    'type' => 'sell',
                    'price' => $rate_now['ticker']['last'],
                    'idr' => '',
                    $coin => $coin_didapat,
                ];
                $sell = Indodax($key, $secretKey, $data);
                $ngantri = 1;
                sleep(2);
                while ($ngantri < 2) {
                    $infohis = [
                        'method' => 'orderHistory',
                        'timestamp' => '1578304294000',
                        'recvWindow' => '1578303937000',
                        'pair' => $coin . '_idr',
                        'count' => '1',
                        'from' => ''
                    ];
                    $pending = Indodax($key, $secretKey, $infohis);
                    if ($pending['return']['orders'][0]["status"] != "filled") {
                        echo "Dalam tahap membeli coin. [ORDER BOOK : " . $pending['return']['orders'][0]["order_id"] . "]\n";
                        sleep(3);
                    } else {
                        $ngantri = 99;
                    }
                }
                echo "PROFIT GUYS! \n\n";
                exit();
            } else if ($rate_now['ticker']['last'] <= $target_rugi) {
                $data = [
                    'method' => 'getInfo',
                    'timestamp' => '1578304294000',
                    'recvWindow' => '1578303937000'
                ];
                $prof = Indodax($key, $secretKey, $data);
                $coin_didapat = $prof["return"]["balance"][$coin];
                $data = [
                    'method' => 'trade',
                    'timestamp' => '1578304294000',
                    'recvWindow' => '1578303937000',
                    'pair' => $coin . '_idr',
                    'type' => 'sell',
                    'price' => $rate_now['ticker']['last'],
                    'idr' => '',
                    $coin => $coin_didapat,
                ];
                $sell = Indodax($key, $secretKey, $data);
                $ngantri = 1;
                sleep(2);
                while ($ngantri < 2) {
                    $infohis = [
                        'method' => 'orderHistory',
                        'timestamp' => '1578304294000',
                        'recvWindow' => '1578303937000',
                        'pair' => $coin . '_idr',
                        'count' => '1',
                        'from' => ''
                    ];
                    $pending = Indodax($key, $secretKey, $infohis);
                    if ($pending['return']['orders'][0]["status"] != "filled") {
                        echo "Dalam tahap membeli coin. [ORDER BOOK : " . $pending['return']['orders'][0]["order_id"] . "]\n";
                        sleep(3);
                    } else {
                        $ngantri = 99;
                    }
                }
                echo "LOSE SEMANGAT! \n\n";
                exit();
            }
        }
    } else {
        var_dump($buy);
    }
} else {
    echo "Coin tidak ditemukan.";
}
