<?php

require_once __DIR__ . '/apiRequest.controller.php';

/**
 * VendedoresController — CRUD de vendedores (solo tabla admins con rol vendedor)
 */
class VendedoresController
{
    const TABLE = 'admins';
    const ROL   = 'vendedor';

    public static function listar()
    {
        $res = ApiRequest::get(self::TABLE, [
            'linkTo'  => 'rol_admin',
            'equalTo' => self::ROL,
            'select'  => 'id_admin,name_admin,email_admin,status_admin,goal_type_admin,goal_value_admin,date_created_admin',
            'orderBy' => 'name_admin',
            'orderMode' => 'ASC',
        ]);

        if (!ApiRequest::isSuccess($res)) {
            return ['success' => false, 'message' => 'Error al listar vendedores'];
        }

        $data = empty($res->results) ? [] : (is_array($res->results) ? $res->results : [$res->results]);

        return ['success' => true, 'data' => $data];
    }

    public static function obtener($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido'];
        }

        $res = ApiRequest::get(self::TABLE, [
            'linkTo'  => 'id_admin,rol_admin',
            'equalTo' => $id . ',' . self::ROL,
            'select'  => 'id_admin,name_admin,email_admin,status_admin,goal_type_admin,goal_value_admin',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return ['success' => false, 'message' => 'Vendedor no encontrado'];
        }

        $v = is_array($res->results) ? $res->results[0] : $res->results;

        return ['success' => true, 'data' => $v];
    }

    public static function crear(array $data)
    {
        $name     = trim($data['name_admin'] ?? '');
        $email    = trim($data['email_admin'] ?? '');
        $password = trim($data['password_admin'] ?? '');
        $status   = (int)($data['status_admin'] ?? 1);
        $goalType = $data['goal_type_admin'] ?? 'ventas';
        $goalVal  = (int)($data['goal_value_admin'] ?? 0);

        if ($name === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'Nombre, usuario y contraseña son obligatorios'];
        }

        if (!in_array($goalType, ['ventas', 'numeros'], true)) {
            return ['success' => false, 'message' => 'Tipo de meta inválido'];
        }

        if ($goalVal <= 0) {
            return ['success' => false, 'message' => 'La meta debe ser mayor a 0'];
        }

        if (self::emailExiste($email)) {
            return ['success' => false, 'message' => 'El usuario ya está registrado'];
        }

        $payload = [
            'name_admin'       => $name,
            'email_admin'      => $email,
            'password_admin'   => $password, // texto plano: la API hashea con crypt() vía register=true
            'rol_admin'        => self::ROL,
            'status_admin'     => $status ? 1 : 0,
            'goal_type_admin'  => $goalType,
            'goal_value_admin' => $goalVal,
        ];

        $res = ApiRequest::post(
            self::TABLE . '?register=true&suffix=admin',
            $payload
        );

        if (!ApiRequest::isSuccess($res)) {
            return ['success' => false, 'message' => ApiRequest::getErrorMessage($res)];
        }

        return [
            'success' => true,
            'message' => 'Vendedor creado correctamente',
            'id'      => $res->results->lastId ?? $res->results ?? null,
        ];
    }

    public static function actualizar(array $data)
    {
        $id = (int)($data['id_admin'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID inválido'];
        }

        $actual = self::obtener($id);
        if (!$actual['success']) {
            return $actual;
        }

        $name     = trim($data['name_admin'] ?? '');
        $email    = trim($data['email_admin'] ?? '');
        $password = trim($data['password_admin'] ?? '');
        $status   = (int)($data['status_admin'] ?? 1);
        $goalType = $data['goal_type_admin'] ?? 'ventas';
        $goalVal  = (int)($data['goal_value_admin'] ?? 0);

        if ($name === '' || $email === '') {
            return ['success' => false, 'message' => 'Nombre y usuario son obligatorios'];
        }

        if (!in_array($goalType, ['ventas', 'numeros'], true)) {
            return ['success' => false, 'message' => 'Tipo de meta inválido'];
        }

        if ($goalVal <= 0) {
            return ['success' => false, 'message' => 'La meta debe ser mayor a 0'];
        }

        if (self::emailExiste($email, $id)) {
            return ['success' => false, 'message' => 'El usuario ya está registrado'];
        }

        $payload = [
            'name_admin'       => $name,
            'email_admin'      => $email,
            'status_admin'     => $status ? 1 : 0,
            'goal_type_admin'  => $goalType,
            'goal_value_admin' => $goalVal,
        ];

        if ($password !== '') {
            require_once ROOT_PATH . '/includes/password.helper.php';
            $payload['password_admin'] = apiHashPassword($password);
        }

        $res = ApiRequest::put(
            self::TABLE . "?id={$id}&nameId=id_admin&token=no&except=token_admin",
            $payload
        );

        if (!ApiRequest::isSuccess($res)) {
            return ['success' => false, 'message' => ApiRequest::getErrorMessage($res)];
        }

        return ['success' => true, 'message' => 'Vendedor actualizado'];
    }

    private static function emailExiste(string $email, ?int $excludeId = null): bool
    {
        $res = ApiRequest::get(self::TABLE, [
            'linkTo'  => 'email_admin',
            'equalTo' => $email,
            'select'  => 'id_admin',
        ]);

        if (!ApiRequest::isSuccess($res) || empty($res->results)) {
            return false;
        }

        $rows = is_array($res->results) ? $res->results : [$res->results];

        foreach ($rows as $row) {
            if ($excludeId === null || (int) $row->id_admin !== $excludeId) {
                return true;
            }
        }

        return false;
    }
}
