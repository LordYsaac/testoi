(function () {
    'use strict';

    /* ---------------------------------------------------------------------
       Tema claro/oscuro. Se guarda en una cookie (NO localStorage) para que
       el propio PHP pueda leer la preferencia en la siguiente carga si se
       desea renderizar el atributo data-bs-theme desde el servidor.
       ------------------------------------------------------------------- */
    function fijarCookieTema(tema) {
        document.cookie = 'tema=' + tema + ';path=/;max-age=' + (60 * 60 * 24 * 365) + ';samesite=Lax';
    }

    function alternarTema() {
        var actual = document.documentElement.getAttribute('data-bs-theme') || 'claro';
        var nuevo = actual === 'oscuro' ? 'claro' : 'oscuro';
        document.documentElement.setAttribute('data-bs-theme', nuevo === 'oscuro' ? 'dark' : 'light');
        document.documentElement.setAttribute('data-tema-app', nuevo);
        fijarCookieTema(nuevo);
        actualizarIconoTema(nuevo);
    }

    function actualizarIconoTema(tema) {
        var icono = document.getElementById('icono-tema');
        if (icono) {
            icono.className = tema === 'oscuro' ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btnTema = document.getElementById('btn-tema');
        if (btnTema) {
            btnTema.addEventListener('click', alternarTema);
            var temaActual = document.documentElement.getAttribute('data-tema-app') || 'claro';
            actualizarIconoTema(temaActual);
        }

        /* Sidebar movil */
        var btnMenu = document.getElementById('btn-menu-movil');
        var sidebar = document.querySelector('.app-sidebar');
        if (btnMenu && sidebar) {
            btnMenu.addEventListener('click', function () {
                sidebar.classList.toggle('mostrar');
            });
        }

        /* Confirmacion para acciones destructivas (desactivar/eliminar/anular) */
        document.querySelectorAll('[data-confirmar]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var mensaje = form.getAttribute('data-confirmar') || '¿Confirma esta accion?';
                if (!window.confirm(mensaje)) {
                    e.preventDefault();
                }
            });
        });

        /* Auto-cierre de alertas flash */
        document.querySelectorAll('.alert-flash').forEach(function (alerta) {
            setTimeout(function () {
                alerta.classList.add('fade');
                setTimeout(function () { alerta.remove(); }, 400);
            }, 5000);
        });

        inicializarFilasDinamicas();
        inicializarBusquedaClientes();
    });

    /* ---------------------------------------------------------------------
       Filas dinamicas para listas simples (diagnosticos, tratamientos):
       <div data-filas-dinamicas data-nombre-campo="diagnosticos">
     ------------------------------------------------------------------- */
    function inicializarFilasDinamicas() {
        document.querySelectorAll('[data-filas-dinamicas]').forEach(function (contenedor) {
            var campo = contenedor.getAttribute('data-nombre-campo');
            var placeholder = contenedor.getAttribute('data-placeholder') || 'Detalle...';
            var btnAgregar = contenedor.querySelector('[data-agregar-fila]');

            function crearFila(valor) {
                var fila = document.createElement('div');
                fila.className = 'input-group mb-2';
                fila.innerHTML =
                    '<input type="text" class="form-control" name="' + campo + '[]" placeholder="' + placeholder + '" value="' + (valor || '').replace(/"/g, '&quot;') + '">' +
                    '<button class="btn btn-outline-secondary" type="button" data-quitar-fila><i class="bi bi-x-lg"></i></button>';
                contenedor.insertBefore(fila, btnAgregar);
            }

            contenedor.addEventListener('click', function (e) {
                if (e.target.closest('[data-quitar-fila]')) {
                    e.target.closest('.input-group').remove();
                }
            });

            if (btnAgregar) {
                btnAgregar.addEventListener('click', function () { crearFila(''); });
            }

            if (!contenedor.querySelector('input')) {
                crearFila('');
            }
        });
    }

    /* ---------------------------------------------------------------------
       Buscador de clientes con AJAX (usado en pantallas de creacion rapida
       de expedientes/recetas desde otros modulos)
       ------------------------------------------------------------------- */
    function inicializarBusquedaClientes() {
        var input = document.getElementById('buscador-clientes');
        if (!input) return;

        var resultados = document.getElementById('resultados-clientes');
        var base = input.getAttribute('data-base-url') || '';
        var modoSeleccion = input.getAttribute('data-modo') === 'seleccionar';
        var campoDestino = modoSeleccion ? document.getElementById('cliente_id_seleccionado') : null;
        var temporizador = null;

        input.addEventListener('input', function () {
            clearTimeout(temporizador);
            var q = input.value.trim();
            if (q.length < 2) {
                resultados.innerHTML = '';
                return;
            }
            temporizador = setTimeout(function () {
                fetch(base + '/clientes/buscar?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (datos) {
                        resultados.innerHTML = '';
                        if (!datos.length) {
                            resultados.innerHTML = '<div class="list-group-item text-muted-soft small">Sin resultados</div>';
                            return;
                        }
                        datos.forEach(function (c) {
                            var etiqueta = c.nombres + ' ' + c.apellidos + ' — ' + c.codigo_cliente;
                            var item = document.createElement(modoSeleccion ? 'button' : 'a');
                            if (modoSeleccion) {
                                item.type = 'button';
                                item.addEventListener('click', function () {
                                    campoDestino.value = c.id;
                                    input.value = etiqueta;
                                    resultados.innerHTML = '';
                                });
                            } else {
                                item.href = base + '/clientes/ver/' + c.id;
                            }
                            item.className = 'list-group-item list-group-item-action';
                            item.textContent = etiqueta;
                            resultados.appendChild(item);
                        });
                    })
                    .catch(function () { /* silencioso: la busqueda no es critica */ });
            }, 300);
        });
    }
})();
