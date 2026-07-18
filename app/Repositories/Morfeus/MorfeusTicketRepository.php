<?php

namespace App\Repositories\Morfeus;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class MorfeusTicketRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, object>
     */
    public function dispatchTicketsForCashier(int $morfeusUserId, array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);
        $mode = $filters['mode'] ?? 'pending_warehouse';

        if ($mode === 'pending_warehouse') {
            return $this->pendingInvoiceTicketsForCashier($morfeusUserId, $filters, $perPage, $page);
        }

        $query = DB::connection('morfeus_sqlsrv')
            ->table('Ven_CabDespachos as d')
            ->leftJoin('Inv_Bodega as b', 'b.eCodigo', '=', 'd.eBodega')
            ->leftJoin('Usuario as u', 'u.Codigo', '=', 'd.eUsuario')
            ->leftJoin('Ven_CabFactura as f', 'f.eIdDoc', '=', 'd.eIdFactura')
            ->leftJoin('Ven_Cliente as c', 'c.eCodigo', '=', 'f.eCliente')
            ->select([
                'd.eCodigo as despacho_id',
                'd.fFecha as fecha_despacho',
                'd.eBodega as bodega_id',
                'b.aDescripcion as bodega',
                'd.eIdFactura as documento_origen_id',
                'd.aNDocumento as numero_ticket',
                'd.eSecuencia as secuencia',
                'd.eUsuario as usuario_id',
                'u.Nombre as usuario',
                'd.aEstado as estado_morfeus',
                'f.eCliente as cliente_id',
                'f.aClienteNombre as cliente_nombre',
                'f.aCedRucCliente as cliente_identificacion',
                'f.aCorreoClte as cliente_correo',
                'f.aNombreDestinatario as destinatario_nombre',
                'f.aCedRucDestinatario as destinatario_identificacion',
                'f.aTelefonoDestinatario as destinatario_telefono',
                'c.aNombre as cliente_catalogo_nombre',
                'c.aCedRuc as cliente_catalogo_identificacion',
                'c.aDireccion as cliente_catalogo_direccion',
                'c.aTelefono as cliente_catalogo_telefono',
                'c.aCorreo as cliente_catalogo_correo',
            ]);

        return $query
            ->where('d.eUsuario', $morfeusUserId)
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('d.fFecha', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('d.fFecha', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('d.aEstado', $status))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = "%{$search}%";

                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('d.aNDocumento', 'like', $term)
                        ->orWhere('b.aDescripcion', 'like', $term);
                });
            })
            ->orderByDesc('d.eCodigo')
            ->get()
            ->pipe(fn (Collection $tickets): LengthAwarePaginator => $this->paginateCollection($tickets, $perPage, $page));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, object>
     */
    private function pendingInvoiceTicketsForCashier(int $morfeusUserId, array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return DB::connection('morfeus_sqlsrv')
            ->table('Ven_CabFactura as f')
            ->join('Ven_DetFactura as df', 'df.eiddoc', '=', 'f.eIdDoc')
            ->leftJoin('Inv_Bodega as b', 'b.eCodigo', '=', 'df.eBodEntrega')
            ->leftJoin('Usuario as u', 'u.Codigo', '=', 'f.eUsuario')
            ->leftJoin('Ven_Cliente as c', 'c.eCodigo', '=', 'f.eCliente')
            ->select([
                'f.eIdDoc as factura_id',
                'f.fFecha as fecha_factura',
                'df.eBodEntrega as bodega_id',
                'b.aDescripcion as bodega',
                'f.eNumeroFactura as numero_factura',
                'f.aSecuencia as secuencia_factura',
                'f.eUsuario as usuario_id',
                'u.Nombre as usuario',
                'f.aEstado as estado_morfeus',
                'f.eCliente as cliente_id',
                'f.aClienteNombre as cliente_nombre',
                'f.aCedRucCliente as cliente_identificacion',
                'f.aCorreoClte as cliente_correo',
                'f.aNombreDestinatario as destinatario_nombre',
                'f.aCedRucDestinatario as destinatario_identificacion',
                'f.aTelefonoDestinatario as destinatario_telefono',
                'f.aObservacion as observacion_factura',
                'c.aNombre as cliente_catalogo_nombre',
                'c.aCedRuc as cliente_catalogo_identificacion',
                'c.aDireccion as cliente_catalogo_direccion',
                'c.aTelefono as cliente_catalogo_telefono',
                'c.aCorreo as cliente_catalogo_correo',
            ])
            ->selectRaw('COUNT(df.eLinea) as items_count')
            ->selectRaw('SUM(CASE WHEN ISNULL(df.dCantidad, 0) > ISNULL(df.dCantidadEnt, 0) THEN 1 ELSE 0 END) as pending_items_count')
            ->selectRaw('SUM(CASE WHEN ISNULL(df.dCantidad, 0) > ISNULL(df.dCantidadEnt, 0) THEN ISNULL(df.dCantidad, 0) - ISNULL(df.dCantidadEnt, 0) ELSE 0 END) as pending_quantity')
            ->where('f.eUsuario', $morfeusUserId)
            ->whereNotNull('df.eBodEntrega')
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('f.fFecha', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('f.fFecha', '<=', $date))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('f.aEstado', $status))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $term = "%{$search}%";

                $query->where(function ($query) use ($term): void {
                    $query
                        ->where('f.aSecuencia', 'like', $term)
                        ->orWhereRaw('CAST(f.eNumeroFactura AS varchar(50)) LIKE ?', [$term])
                        ->orWhere('b.aDescripcion', 'like', $term);
                });
            })
            ->groupBy([
                'f.eIdDoc',
                'f.fFecha',
                'df.eBodEntrega',
                'b.aDescripcion',
                'f.eNumeroFactura',
                'f.aSecuencia',
                'f.eUsuario',
                'u.Nombre',
                'f.aEstado',
                'f.eCliente',
                'f.aClienteNombre',
                'f.aCedRucCliente',
                'f.aCorreoClte',
                'f.aNombreDestinatario',
                'f.aCedRucDestinatario',
                'f.aTelefonoDestinatario',
                'f.aObservacion',
                'c.aNombre',
                'c.aCedRuc',
                'c.aDireccion',
                'c.aTelefono',
                'c.aCorreo',
            ])
            ->havingRaw('SUM(CASE WHEN ISNULL(df.dCantidad, 0) > ISNULL(df.dCantidadEnt, 0) THEN 1 ELSE 0 END) > 0')
            ->orderByDesc('f.eIdDoc')
            ->get()
            ->pipe(fn (Collection $tickets): LengthAwarePaginator => $this->paginateCollection($tickets, $perPage, $page));
    }

    /**
     * SQL Server de Morfeus puede estar en compatibilidad antigua y rechazar OFFSET/FETCH.
     *
     * @param  Collection<int, object>|LazyCollection<int, object>  $records
     * @return LengthAwarePaginator<int, object>
     */
    private function paginateCollection(Collection|LazyCollection $records, int $perPage, int $page): LengthAwarePaginator
    {
        $records = $records instanceof LazyCollection ? $records->collect() : $records;

        return new LengthAwarePaginator(
            items: $records->forPage($page, $perPage)->values(),
            total: $records->count(),
            perPage: $perPage,
            currentPage: $page,
            options: ['pageName' => 'page'],
        );
    }

    public function pendingInvoiceForCashier(int $morfeusUserId, int $invoiceId, int $warehouseId): ?object
    {
        return DB::connection('morfeus_sqlsrv')
            ->table('Ven_CabFactura as f')
            ->leftJoin('Usuario as u', 'u.Codigo', '=', 'f.eUsuario')
            ->leftJoin('Inv_Bodega as b', 'b.eCodigo', '=', DB::raw($warehouseId))
            ->leftJoin('Ven_Cliente as c', 'c.eCodigo', '=', 'f.eCliente')
            ->select([
                'f.eIdDoc as factura_id',
                'f.fFecha as fecha_factura',
                'f.eNumeroFactura as numero_factura',
                'f.aSecuencia as secuencia_factura',
                'f.eUsuario as usuario_id',
                'u.Nombre as usuario',
                'f.aEstado as estado_morfeus',
                'b.eCodigo as bodega_id',
                'b.aDescripcion as bodega',
                'f.eCliente as cliente_id',
                'f.aClienteNombre as cliente_nombre',
                'f.aCedRucCliente as cliente_identificacion',
                'f.aCorreoClte as cliente_correo',
                'f.aNombreDestinatario as destinatario_nombre',
                'f.aCedRucDestinatario as destinatario_identificacion',
                'f.aTelefonoDestinatario as destinatario_telefono',
                'f.aObservacion as observacion_factura',
                'c.aNombre as cliente_catalogo_nombre',
                'c.aCedRuc as cliente_catalogo_identificacion',
                'c.aDireccion as cliente_catalogo_direccion',
                'c.aTelefono as cliente_catalogo_telefono',
                'c.aCorreo as cliente_catalogo_correo',
            ])
            ->where('f.eUsuario', $morfeusUserId)
            ->where('f.eIdDoc', $invoiceId)
            ->whereExists(function ($query) use ($invoiceId, $warehouseId): void {
                $query
                    ->selectRaw('1')
                    ->from('Ven_DetFactura as df')
                    ->whereColumn('df.eiddoc', 'f.eIdDoc')
                    ->where('df.eiddoc', $invoiceId)
                    ->where('df.eBodEntrega', $warehouseId)
                    ->whereRaw('ISNULL(df.dCantidad, 0) > ISNULL(df.dCantidadEnt, 0)');
            })
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    public function pendingInvoiceItems(int $invoiceId, int $warehouseId): Collection
    {
        return DB::connection('morfeus_sqlsrv')
            ->table('Ven_DetFactura as df')
            ->leftJoin('Inv_Item as i', 'i.eCodigo', '=', 'df.eItem')
            ->leftJoin('Inv_Unidades as un', 'un.eCodigo', '=', 'df.eUnidad')
            ->select([
                'df.eiddoc as factura_id',
                'df.eLinea as linea',
                'df.eItem as item_id',
                'i.aCodBar as codigo_barra',
                'i.aCodigoAlterno as codigo_alterno',
                'i.aDescripcion as producto',
                'df.eUnidad as unidad_id',
                'un.aSiglas as unidad',
                'df.dCantidad as cantidad_facturada',
                'df.dCantidadDes as cantidad_despachada_factura',
                'df.dCantidadEnt as cantidad_entregada_factura',
            ])
            ->selectRaw('(ISNULL(df.dCantidad, 0) - ISNULL(df.dCantidadEnt, 0)) as cantidad_pendiente')
            ->where('df.eiddoc', $invoiceId)
            ->where('df.eBodEntrega', $warehouseId)
            ->whereRaw('ISNULL(df.dCantidad, 0) > ISNULL(df.dCantidadEnt, 0)')
            ->orderBy('df.eLinea')
            ->get();
    }

    public function dispatchTicketForCashier(int $morfeusUserId, int $externalDispatchId): ?object
    {
        return DB::connection('morfeus_sqlsrv')
            ->table('Ven_CabDespachos as d')
            ->leftJoin('Inv_Bodega as b', 'b.eCodigo', '=', 'd.eBodega')
            ->leftJoin('Usuario as u', 'u.Codigo', '=', 'd.eUsuario')
            ->leftJoin('Ven_CabFactura as f', 'f.eIdDoc', '=', 'd.eIdFactura')
            ->leftJoin('Ven_Cliente as c', 'c.eCodigo', '=', 'f.eCliente')
            ->select([
                'd.eCodigo as despacho_id',
                'd.fFecha as fecha_despacho',
                'd.eBodega as bodega_id',
                'b.aDescripcion as bodega',
                'd.eIdFactura as documento_origen_id',
                'd.aNDocumento as numero_ticket',
                'd.eSecuencia as secuencia',
                'd.eUsuario as usuario_id',
                'u.Nombre as usuario',
                'd.aEstado as estado_morfeus',
                'f.eCliente as cliente_id',
                'f.aClienteNombre as cliente_nombre',
                'f.aCedRucCliente as cliente_identificacion',
                'f.aCorreoClte as cliente_correo',
                'f.aNombreDestinatario as destinatario_nombre',
                'f.aCedRucDestinatario as destinatario_identificacion',
                'f.aTelefonoDestinatario as destinatario_telefono',
                'c.aNombre as cliente_catalogo_nombre',
                'c.aCedRuc as cliente_catalogo_identificacion',
                'c.aDireccion as cliente_catalogo_direccion',
                'c.aTelefono as cliente_catalogo_telefono',
                'c.aCorreo as cliente_catalogo_correo',
            ])
            ->where('d.eUsuario', $morfeusUserId)
            ->where('d.eCodigo', $externalDispatchId)
            ->first();
    }

    /**
     * @return Collection<int, object>
     */
    public function dispatchTicketItems(int $externalDispatchId, bool $pendingOnly = false): Collection
    {
        return DB::connection('morfeus_sqlsrv')
            ->table('Ven_DetDespacho as dd')
            ->leftJoin('Inv_Item as i', 'i.eCodigo', '=', 'dd.eItem')
            ->leftJoin('Inv_Unidades as un', 'un.eCodigo', '=', 'dd.eUnidad')
            ->select([
                'dd.eDespacho as despacho_id',
                'dd.eLinea as linea',
                'dd.eItem as item_id',
                'i.aCodBar as codigo_barra',
                'i.aCodigoAlterno as codigo_alterno',
                'i.aDescripcion as producto',
                'dd.eUnidad as unidad_id',
                'un.aSiglas as unidad',
                'dd.dCantxDepachar as cantidad_a_despachar',
                'dd.dCantDespachada as cantidad_despachada',
            ])
            ->selectRaw('(ISNULL(dd.dCantxDepachar, 0) - ISNULL(dd.dCantDespachada, 0)) as cantidad_pendiente')
            ->where('dd.eDespacho', $externalDispatchId)
            ->when($pendingOnly, fn ($query) => $query->whereRaw('ISNULL(dd.dCantxDepachar, 0) > ISNULL(dd.dCantDespachada, 0)'))
            ->orderBy('dd.eLinea')
            ->get();
    }

    public function relatedDelivery(int $externalDispatchId): ?object
    {
        return DB::connection('morfeus_sqlsrv')
            ->table('Ven_CabDespachos as d')
            ->leftJoin('Ven_CabEntrega as e', function ($join): void {
                $join
                    ->on('e.eIdFactura', '=', 'd.eIdFactura')
                    ->on('e.eBodega', '=', 'd.eBodega');
            })
            ->select([
                'd.eCodigo as despacho_id',
                'd.aNDocumento as numero_ticket_despacho',
                'd.eIdFactura as documento_origen_id',
                'd.eBodega as bodega_id',
                'd.aEstado as estado_despacho',
                'e.eCodigo as entrega_id',
                'e.aNDocumento as numero_ticket_entrega',
                'e.fFecha as fecha_entrega',
                'e.aEstado as estado_entrega',
                'e.aObservacion as entregado_por',
            ])
            ->where('d.eCodigo', $externalDispatchId)
            ->first();
    }
}
