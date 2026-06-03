<?php

class OpenPayController {

    public static function irAOpenPay(array $data) {

        if (empty($data['id_payment_backup'])) {
            return ['success' => false, 'message' => 'ID respaldo requerido'];
        }

        $backup = ApiRequest::get('payment_backups', [
            'linkTo'  => 'id_payment_backup',
            'equalTo' => $data['id_payment_backup']
        ]);

        if (!ApiRequest::isSuccess($backup) || empty($backup->results)) {
            return ['success' => false, 'message' => 'Respaldo no encontrado'];
        }

        $b = $backup->results[0];

        // Limpiar número de teléfono (quitar código país +57 y 0 inicial)
        $phoneNumber = preg_replace('/^(\+?57)?0?/', '', $data['phone_customer']);

        $payload = [
            'method'      => 'bank_account',
            'amount'      => (float)$b->amount_payment_backup,
            'currency'    => 'COP',
            'iva'         => '0',
            'description' => 'Compra Alquiler Ap fenix',
            'order_id'    => $b->code_payment_backup,

            'redirect_url' => OPENPAY_RETURN_URL . '?order_id=' . $b->code_payment_backup,

            'customer' => [
                'name'         => $data['name_customer'],
                'last_name'    => $data['lastname_customer'],
                'email'        => $data['email_customer'],
                'phone_number' => $phoneNumber,
                'requires_account' => false,
                'customer_address' => [
                    'department' => $data['department_customer'] ?? 'N/A',
                    'city'       => $data['city_customer'] ?? 'N/A',
                    'additional' => $data['address_customer'] ?? 'N/A'
                ]
            ],

            'device_session_id' => $data['device_session_id'] ?? uniqid('dev_', true)
        ];

        // Si tienes metadata para antifraude, agrégalo aquí
        if (!empty($data['metadata'])) {
            $payload['metadata'] = $data['metadata'];
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => OPENPAY_URL . '/charges',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(OPENPAY_PRIVATE_KEY . ':')
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($httpCode === 200 || $httpCode === 201) {

            // Guardar referencia OpenPay
            ApiRequest::put(
                "payment_backups?id={$b->id_payment_backup}&nameId=id_payment_backup&token=no",
                [
                    'openpay_id_payment_backup'     => $responseData['id'],
                    'openpay_status_payment_backup' => $responseData['status'],
                    'openpay_response_payment_backup' => json_encode($responseData)
                ]
            );

            return [
                'success'      => true,
                'redirect_url' => $responseData['payment_method']['url']
            ];
        }
    }
}