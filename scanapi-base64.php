<?php

// Mandatory claims

// You may copy paste the full sample code provided below, on this page, under the code section titled : (Sample Code : Full PHP Implementation (Method 2))

$mobicard_version = "2.0";
$mobicard_mode = "LIVE"; // production
$mobicard_merchant_id = "4";
$mobicard_api_key = "YmJkOGY0OTZhMTU2ZjVjYTIyYzFhZGQyOWRiMmZjMmE2ZWU3NGIxZWM3ZTBiZSJ9";
$mobicard_secret_key = "NjIwYzEyMDRjNjNjMTdkZTZkMjZhOWNiYjIxNzI2NDQwYzVmNWNiMzRhMzBjYSJ9";

$mobicard_token_id = abs(rand(1000000,1000000000));
$mobicard_token_id = "$mobicard_token_id";

$mobicard_txn_reference = abs(rand(1000000,1000000000));
$mobicard_txn_reference = "$mobicard_txn_reference";

$mobicard_service_id = "20000"; // Scan Card service ID
$mobicard_service_type = "2"; // Use '2' for CARD SCAN METHOD 2

// Prepare base64 string from image
// Method A: From file path
// $scanned_card_photo_url_path = "/path/to/your/card_image.jpg";
$scanned_card_photo_url_path =  "https://mobicardsystems.com/scan_card_photo_one.jpg"; // /path/to/your/card_image
$mobicard_scan_card_photo_base64_string = base64_encode(file_get_contents($scanned_card_photo_url_path));

// Method B: From uploaded file
if(isset($_FILES['card_image']) && $_FILES['card_image']['error'] == 0) {
    $mobicard_scan_card_photo_base64_string = base64_encode(file_get_contents($_FILES['card_image']['tmp_name']));
}

// Method C: From base64 data URL (frontend JavaScript)
if(isset($_POST['base64_image'])) {
    $base64_data = $_POST['base64_image'];
    // Remove data:image/jpeg;base64, prefix if present
    if (strpos($base64_data, 'base64,') !== false) {
        $base64_data = substr($base64_data, strpos($base64_data, 'base64,') + 7);
    }
    $mobicard_scan_card_photo_base64_string = $base64_data;
}

$mobicard_extra_data = "your_custom_data_here_will_be_returned_as_is";

// Create JWT Header
$mobicard_jwt_header = [
    "typ" => "JWT",
    "alg" => "HS256"
];
$mobicard_jwt_header = rtrim(strtr(base64_encode(json_encode($mobicard_jwt_header)), '+/', '-_'), '=');

// Create JWT Payload
$mobicard_jwt_payload = array(
    "mobicard_version" => "$mobicard_version",
    "mobicard_mode" => "$mobicard_mode",
    "mobicard_merchant_id" => "$mobicard_merchant_id",
    "mobicard_api_key" => "$mobicard_api_key",
    "mobicard_service_id" => "$mobicard_service_id",
    "mobicard_service_type" => "$mobicard_service_type",
    "mobicard_token_id" => "$mobicard_token_id",
    "mobicard_txn_reference" => "$mobicard_txn_reference",
    "mobicard_scan_card_photo_base64_string" => "$mobicard_scan_card_photo_base64_string",
    "mobicard_extra_data" => "$mobicard_extra_data"
);

$mobicard_jwt_payload = rtrim(strtr(base64_encode(json_encode($mobicard_jwt_payload)), '+/', '-_'), '=');

// Generate Signature
$header_payload = $mobicard_jwt_header . '.' . $mobicard_jwt_payload;
$mobicard_jwt_signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $header_payload, $mobicard_secret_key, true)), '+/', '-_'), '=');

// Create Final JWT
$mobicard_auth_jwt = "$mobicard_jwt_header.$mobicard_jwt_payload.$mobicard_jwt_signature";

// Make API Call
$mobicard_request_access_token_url = "https://mobicardsystems.com/api/v1/card_scan";

$mobicard_curl_post_data = array('mobicard_auth_jwt' => $mobicard_auth_jwt);

$curl_mobicard = curl_init();
curl_setopt($curl_mobicard, CURLOPT_URL, $mobicard_request_access_token_url);
curl_setopt($curl_mobicard, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_mobicard, CURLOPT_POST, true);
curl_setopt($curl_mobicard, CURLOPT_POSTFIELDS, json_encode($mobicard_curl_post_data));
curl_setopt($curl_mobicard, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl_mobicard, CURLOPT_SSL_VERIFYPEER, false);
$mobicard_curl_response = curl_exec($curl_mobicard);
curl_close($curl_mobicard);

// Parse Response
$response_data = json_decode($mobicard_curl_response, true);

if(isset($response_data) && is_array($response_data)) {
    if($response_data['status'] === 'SUCCESS') {
        // Extract all response data
        $card_number = $response_data['card_information']['card_number'];
        $card_expiry = $response_data['card_information']['card_expiry_date'];
        $card_brand = $response_data['card_information']['card_brand'];
        $card_bank = $response_data['card_information']['card_bank_name'];
        $confidence_score = $response_data['card_information']['card_confidence_score'];
        $validation_checks = $response_data['card_information']['card_validation_checks'];
        
        // Use the extracted data
        echo "Card Number: " . $response_data['card_information']['card_number_masked'] . "<br>";
        echo "Expiry Date: " . $card_expiry . "<br>";
        echo "Card Brand: " . $card_brand . "<br>";
        echo "Bank: " . $card_bank . "<br>";
        echo "Confidence Score: " . $confidence_score . "<br>";
        
        if($validation_checks['luhn_algorithm']) {
            echo "✓ Luhn Algorithm Check Passed<br>";
        }
        
        if($validation_checks['expiry_date']) {
            echo "✓ Expiry Date is Valid<br>";
        } else {
            echo "⚠ Expired or Invalid Expiry Date<br>";
        }
        
        // Access additional data
        $risk_score = $response_data['card_risk_information']['card_risk_score'];
        $exif_flag = $response_data['card_exif_information']['card_exif_flag'];
        $addendum_data = $response_data['addendum_data'];
        
    } else {
        echo "Error: " . $response_data['status_message'] . " (Code: " . $response_data['status_code'] . ")";
    }
} else {
    echo "Error: Invalid API response";
}
