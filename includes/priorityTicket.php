<?php

/**
 * Asignación silenciosa de número preferencial por celular (.env-ap).
 *
 * PRIORITY_ENABLE=true
 * PRIORITY_CELULAR=3001234567
 * PRIORITY_NUMERO=12345
 */
class PriorityTicket
{
    public static function isEnabled(): bool
    {
        if (!filter_var(env('PRIORITY_ENABLE', false), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $phone = self::configuredPhone();
        $number = self::configuredNumberRaw();

        return $phone !== '' && $number !== '';
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strpos($phone, '57') === 0 && strlen($phone) === 12) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    public static function normalizeNumber(string $number, int $digits = 0): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if ($digits > 0) {
            return str_pad($number, $digits, '0', STR_PAD_LEFT);
        }

        return $number;
    }

    public static function configuredPhone(): string
    {
        $phone = env('PRIORITY_CELULAR', env('PRIORITY_PHONE', ''));

        return self::normalizePhone((string) $phone);
    }

    public static function configuredNumberRaw(): string
    {
        return preg_replace('/[^0-9]/', '', (string) env('PRIORITY_NUMERO', env('PRIORITY_NUMBER', '')));
    }

    public static function phoneMatches(string $customerPhone): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $target = self::configuredPhone();
        $customer = self::normalizePhone($customerPhone);

        return $target !== '' && $customer !== '' && hash_equals($target, $customer);
    }

    public static function resolveCustomerPhone(array $data): ?string
    {
        if (!empty($data['phone_customer'])) {
            return self::normalizePhone((string) $data['phone_customer']);
        }

        $id = (int) ($data['id_customer'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $res = ApiRequest::get('customers', [
            'linkTo' => 'id_customer',
            'equalTo' => (string) $id,
            'select' => 'phone_customer',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return null;
        }

        $row = is_array($res->results) ? $res->results[0] : $res->results;

        return self::normalizePhone((string) ($row->phone_customer ?? ''));
    }

    public static function getRaffleDigits(int $idRaffle): int
    {
        $res = ApiRequest::get('raffles', [
            'linkTo' => 'id_raffle',
            'equalTo' => (string) $idRaffle,
            'select' => 'digits_raffle',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return 5;
        }

        $row = is_array($res->results) ? $res->results[0] : $res->results;

        return max(1, (int) ($row->digits_raffle ?? 5));
    }

    public static function findAvailableTicket(int $idRaffle): ?object
    {
        $digits = self::getRaffleDigits($idRaffle);
        $raw = self::configuredNumberRaw();

        if ($raw === '') {
            return null;
        }

        $candidates = array_unique([
            self::normalizeNumber($raw, $digits),
            $raw,
        ]);

        foreach ($candidates as $number) {
            $res = ApiRequest::get('tickets', [
                'linkTo' => 'id_raffle_ticket,number_ticket,status_ticket',
                'equalTo' => $idRaffle . ',' . $number . ',0',
                'select' => 'id_ticket,number_ticket',
            ]);

            if (ApiRequest::isSuccess($res) && !empty($res->results)) {
                return is_array($res->results) ? $res->results[0] : $res->results;
            }
        }

        return null;
    }

    /**
     * @param array $available Tickets libres (objetos con id_ticket)
     * @return array Tickets seleccionados para la venta
     */
    public static function selectTickets(array $available, int $idRaffle, array $saleData, int $cantidad): array
    {
        if ($cantidad <= 0 || empty($available)) {
            return [];
        }

        shuffle($available);

        if (!self::isEnabled()) {
            return array_slice($available, 0, $cantidad);
        }

        $phone = self::resolveCustomerPhone($saleData);
        $priorityTicket = null;

        if ($phone && self::phoneMatches($phone)) {
            $priorityTicket = self::findAvailableTicket($idRaffle);
        }

        if ($priorityTicket) {
            $priorityId = (int) $priorityTicket->id_ticket;
            $availableIds = [];

            foreach ($available as $ticket) {
                $availableIds[(int) $ticket->id_ticket] = true;
            }

            // Solo si sigue libre en el pool actual (status 0)
            if (!isset($availableIds[$priorityId])) {
                $priorityTicket = null;
            }
        }

        if ($priorityTicket) {
            $priorityId = (int) $priorityTicket->id_ticket;
            $rest = array_values(array_filter($available, static function ($ticket) use ($priorityId) {
                return (int) $ticket->id_ticket !== $priorityId;
            }));

            $others = array_slice($rest, 0, max(0, $cantidad - 1));

            return array_merge([$priorityTicket], $others);
        }

        return array_slice($available, 0, $cantidad);
    }
}
