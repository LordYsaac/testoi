-- Agrega el registro del vuelto/cambio entregado al cliente en pagos en
-- efectivo que superan el total de la factura. Antes, el monto excedente
-- se registraba completo como ingreso de caja, inflando el efectivo
-- esperado al cerrar caja exactamente por el monto del vuelto.
ALTER TABLE facturas
    ADD COLUMN vuelto DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER saldo_pendiente;
