<?php

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Cliente;

/**
 * Endpoints de ejemplo, totalmente funcionales, que demuestran el patron a
 * seguir para exponer el resto de modulos (productos, facturas, citas...)
 * en fases posteriores. Ver docs/API.md para el contrato completo.
 */
class ClientesApiController extends ApiController
{
    public function index(): void
    {
        [$pagina, $porPagina] = $this->paginacion();
        $busqueda = (string) Request::input('q', '');

        $clienteModel = new Cliente();
        $clientes = $clienteModel->listar($busqueda, 'activo', $porPagina, $pagina);
        $total = $clienteModel->totalListar($busqueda, 'activo');

        $this->json([
            'data' => $clientes,
            'meta' => [
                'pagina' => $pagina,
                'por_pagina' => $porPagina,
                'total' => $total,
                'total_paginas' => (int) ceil($total / $porPagina),
            ],
        ]);
    }

    public function show(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->error('El identificador debe ser numerico.', 422);
            return;
        }

        $cliente = (new Cliente())->conDetalle((int) $id);
        if (!$cliente) {
            $this->error('Cliente no encontrado.', 404);
            return;
        }

        $this->json(['data' => $cliente]);
    }

    public function store(): void
    {
        $data = Request::only(['nombres', 'apellidos', 'telefono', 'email', 'cedula_pasaporte']);

        $v = new Validator($data);
        $v->required('nombres')->required('apellidos')->email('email');
        if ($v->fails()) {
            $this->error('Datos invalidos.', 422);
            return;
        }

        $clienteModel = new Cliente();
        if (!empty($data['cedula_pasaporte']) && $clienteModel->cedulaExiste($data['cedula_pasaporte'])) {
            $this->error('Ya existe un cliente con esa cedula/pasaporte.', 409);
            return;
        }

        $id = $clienteModel->create($data);
        $this->json(['data' => $clienteModel->conDetalle($id)], 201);
    }
}
