<?php
function verifyData($required_fields, $data)
{
    /* Verify Data Complete */
    foreach ($required_fields as $field) {
        if (!array_key_exists($field, $data) || empty($data[$field])) {
            return $field;
        }
    }
}

function passwordVerify($password, $passwordHash)
{
    $response = password_verify($password, $passwordHash) ? 1 : 0;
    return $response;
}
?>