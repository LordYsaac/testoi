# Manual de Usuario

Guía de uso de los módulos disponibles en esta entrega. Los módulos de
Inventario, Facturación, Compras y Caja se documentarán cuando se
construya su interfaz (ver `docs/ROADMAP.md`).

## Iniciar sesión

1. Abra el sistema en su navegador.
2. Ingrese su usuario y contraseña.
3. Si es su primer ingreso, el sistema le pedirá crear una nueva
   contraseña (mínimo 8 caracteres).
4. Si olvida su contraseña, pida a un Administrador que use el botón
   **"Restablecer contraseña"** en *Usuarios* — genera una temporal que
   usted deberá cambiar al ingresar.

Tras 5 intentos fallidos, la cuenta se bloquea 15 minutos por seguridad.

## Panel principal (Dashboard)

Muestra de un vistazo: ventas de hoy y del mes, citas del día, productos
con stock bajo, cuentas por cobrar vencidas y sus notificaciones
pendientes. Es la pantalla de inicio tras el login.

## Clientes

**Ver listado:** menú lateral → *Clientes*. Use el buscador para
encontrar por nombre, código, cédula o teléfono; filtre por estado.

**Registrar un cliente nuevo:** botón *"+"* junto al buscador. Los únicos
campos obligatorios son nombres y apellidos — todo lo demás (foto,
contacto, seguro médico, cédula) es opcional pero recomendado.

**Ficha del cliente:** al hacer clic en un cliente se abren 4 pestañas:

- **Información:** datos de contacto y seguro médico.
- **Antecedentes:** antecedentes familiares, personales, quirúrgicos,
  alergias y medicamentos. Es un registro **vivo**: se actualiza aquí una
  vez y queda disponible para consulta en cada nueva entrada clínica —
  no hay que volver a escribirlo en cada visita.
- **Historial clínico:** todas las consultas anteriores, en orden
  cronológico.
- **Recetas:** todas las recetas emitidas al paciente.

Desde la ficha del cliente puede iniciar directamente una **Nueva
consulta** o **Emitir receta** con los botones superiores.

## Historial clínico (consulta oftalmológica)

Al crear una nueva entrada, el formulario se organiza en secciones
plegables para no abrumar con todos los campos a la vez:

1. **Consulta:** doctor/optómetra que atiende, motivo de consulta,
   próxima cita sugerida.
2. **Agudeza visual:** OD/OI, con y sin corrección, visión cercana/lejana.
3. **Refracción:** esfera, cilindro, eje, adición, prisma por ojo, más
   distancia pupilar.
4. **Queratometría y tonometría.**
5. **Biomicroscopía** (lámpara de hendidura) por estructura del ojo.
6. **Fondo de ojo** por estructura.
7. **Diagnóstico y tratamiento:** puede agregar tantas líneas de
   diagnóstico y tratamiento como necesite con el botón *"+ Agregar"*, más
   indicaciones y observaciones generales.

**Ninguna sección es obligatoria excepto el motivo de consulta** — llene
solo lo que aplique a esa visita; los campos vacíos simplemente no se
muestran al ver el expediente después.

Puede adjuntar PDFs, imágenes o estudios desde la vista de detalle del
expediente, una vez guardado.

## Recetas

Desde la ficha de un cliente, botón **"Emitir receta"**: seleccione el
especialista, la graduación (OD/OI: esfera, cilindro, eje, adición, DP),
y las especificaciones del lente (tipo, material, color, tratamiento).

**Imprimir:** genera un documento con el membrete de la óptica, la
graduación, espacio de firma y un **código QR**. Cualquier persona puede
escanear ese código para verificar que la receta es auténtica y no ha
sido anulada, sin necesidad de iniciar sesión.

**Anular:** si se emitió por error, un usuario con permiso puede
anularla; queda marcada como "ANULADA — NO VÁLIDA" incluso en copias ya
impresas si vuelven a validarse por QR.

## Inventario

**Ver productos:** menú lateral → *Inventario*. Filtre por categoría o
busque por nombre, código o marca.

**Registrar un producto:** botón *"+"*. Si deja el campo *Código* vacío,
se genera automáticamente (`PROD-000001`, etc.). Puede indicar una
*Existencia inicial* al crearlo — esto registra automáticamente un
movimiento de entrada.

**Ficha del producto:** muestra el stock actual, el mínimo configurado, y
el **Kardex** completo — el historial de todos los movimientos
(entradas, salidas, ajustes) con el saldo acumulado después de cada uno.

**Ajuste rápido de inventario:** desde la ficha del producto, registre
entradas, salidas o ajustes con signo manualmente (por ejemplo, una
rotura o una diferencia detectada en conteo físico). Todo ajuste queda
en el Kardex con el motivo que usted escriba.

> El stock que ve en pantalla **nunca se edita directamente** — siempre es
> el resultado de sumar todos los movimientos del Kardex. Esto garantiza
> que el número de stock y el historial de movimientos nunca se
> desincronicen.

## Proveedores

CRUD simple desde el menú lateral. La ficha de cada proveedor muestra su
**estado de cuenta** (total comprado y saldo pendiente) y el historial
completo de compras registradas.

## Compras

**Registrar una compra:** menú lateral → *Compras* → *"Registrar
compra"*. Seleccione el proveedor, y agregue tantas líneas de productos
como necesite con el botón *"Agregar línea"* — al elegir un producto, su
último costo se autocompleta (editable). El ITBIS se calcula
automáticamente sobre el subtotal; puede desmarcarlo si la compra no
aplica impuesto.

Al guardar, el sistema **en una sola operación**:
1. Registra la compra y sus líneas de detalle.
2. Genera un movimiento de entrada en el inventario por cada producto.
3. Actualiza el costo del producto al último precio de compra.

**Registrar un pago:** desde el detalle de la compra, si queda saldo
pendiente, puede registrar pagos parciales o totales — el estado
(pendiente/parcial/pagada) se actualiza solo.

## Facturación

**Antes de facturar en efectivo, abra su caja** (menú *Caja*) — si no lo
hace, el sistema le dejará facturar igual, pero el pago no quedará
vinculado a ningún arqueo.

**Crear una factura:** menú lateral → *Facturación* → *"+"* (o el botón
*"Facturar"* desde la ficha de un cliente, que ya lo trae preseleccionado).

1. Busque y seleccione el cliente (si no vino preseleccionado).
2. Elija el **tipo**: venta de productos, venta/servicio médico, mixta, o
   cotización (esta última no genera comprobante fiscal ni descuenta
   inventario — es solo un estimado imprimible).
3. Elija el **comprobante fiscal** (NCF): B01 para crédito fiscal, B02
   para consumo. El sistema asigna el siguiente número disponible
   automáticamente — nunca lo escriba a mano.
4. Agregue líneas con *"+ Agregar línea"*: elija un producto (autocompleta
   nombre y precio, editable) o déjelo en "Servicio / sin producto" para
   cobrar algo que no está en inventario (ej. una consulta).
5. Agregue el/los método(s) de pago. Puede dividir el pago entre efectivo
   y tarjeta, por ejemplo — el sistema calcula el saldo pendiente
   automáticamente si el total pagado es menor al total de la factura.

Al guardar, en una sola operación: se registra la factura, se descuenta
el inventario de cada producto vendido, se registra el pago, y si tiene
caja abierta, se refleja ahí también.

**Anular una factura:** repone automáticamente el inventario vendido.
Requiere indicar un motivo.

## Caja

**Abrir:** indique el monto en efectivo con el que empieza el turno.

**Durante el turno:** puede registrar ingresos/egresos manuales (por
ejemplo, un retiro de efectivo o un gasto menor pagado desde la caja).
Las ventas en efectivo/tarjeta se registran solas cuando factura.

**Cerrar:** cuente el efectivo físico e ingréselo. El sistema le muestra
la diferencia contra lo que *debería* haber (apertura + ingresos −
egresos) — útil para detectar faltantes o sobrantes al final del día.

## Cuentas por cobrar

Menú lateral → *Cuentas por cobrar*: lista de clientes con saldo
pendiente, ordenados por días de atraso. Haga clic en *"Gestionar"* para
ver sus facturas pendientes específicas y registrar un abono contra
cualquiera de ellas.

## Seguridad de la cuenta: verificación en dos pasos (2FA)

Menú de usuario (arriba a la derecha) → *"Seguridad (2FA)"*.

**Activar:** clic en *"Activar verificación en dos pasos"* → escanee el
código QR con Google Authenticator, Authy, o cualquier app compatible
(o ingrese la clave manualmente si no puede escanear) → ingrese el
código de 6 dígitos que le muestra la app para confirmar.

Una vez activo, cada inicio de sesión pedirá ese código además de su
contraseña. Si cambia de teléfono, deberá desactivarlo (con su
contraseña) y activarlo de nuevo con la app nueva.

## Agenda médica

Menú lateral → *Agenda médica*: calendario del mes con la cantidad de
citas por día. Haga clic en un día para ver el detalle y agendar,
editar o cambiar el estado de cada cita (pendiente, confirmada,
finalizada, cancelada) directamente desde la lista.

Al agendar, si el doctor seleccionado ya tiene otra cita a esa hora
exacta, el sistema se lo advierte (no bloquea, por si es intencional).

También puede agendar una cita directamente desde la ficha de un
cliente con el botón *"Agendar cita"*.

## Reportes

Menú lateral → *Reportes*: siete reportes listos para consultar y
exportar a CSV (se abre directo en Excel) — ventas por período,
productos más vendidos, clientes frecuentes, inventario valorizado,
pacientes atendidos, recetas emitidas, y cuentas por cobrar/pagar. Los
reportes con rango de fechas muestran el mes actual por defecto; ajuste
"Desde"/"Hasta" y presione Filtrar.

## Usuarios y Roles (solo Administradores)

**Usuarios:** cree una cuenta por cada miembro del personal, asignando su
rol. La contraseña que usted define es temporal — el sistema exige que la
cambien en su primer ingreso.

**Roles:** cada rol (Recepción, Doctor/Oftalmólogo, Optómetra, Cajero,
Inventario, Vendedor, Administrador, Super Administrador) tiene una
matriz de permisos por módulo y acción. Puede ajustar qué puede hacer
cada rol desde *Roles → Ver permisos*. Los cambios de permisos se
reflejan la próxima vez que ese usuario inicie sesión.

## Tema claro/oscuro

El ícono de sol/luna en la barra superior alterna el tema visual; la
preferencia se recuerda en su navegador.

## Preguntas frecuentes

**¿Por qué no veo cierta opción del menú?** Su rol no tiene permiso para
ese módulo. Pídalo a un Administrador si lo necesita para su trabajo.

**Edité un registro pero no veo el cambio.** Verifique que llegó el
mensaje de confirmación verde en la parte superior; si vio un mensaje
rojo, revise los campos marcados en el formulario.

**¿Se puede eliminar un cliente/expediente por error?** Por diseño, los
registros con historial no se eliminan de la base de datos — se
"desactivan". Esto protege el historial clínico y financiero de pérdidas
accidentales. Contacte a un Super Administrador si necesita corregir un
error real.
