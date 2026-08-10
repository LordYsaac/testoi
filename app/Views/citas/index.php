<?php
use App\Core\Url;
$tituloPagina = 'Agenda medica';

$nombresMes = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$diasSemana = ['Dom','Lun','Mar','Mie','Jue','Vie','Sab'];

$primerDiaTs = mktime(0, 0, 0, $mes, 1, $anio);
$diasEnMes = (int) date('t', $primerDiaTs);
$offsetInicio = (int) date('w', $primerDiaTs); // 0=domingo
$hoy = date('Y-m-d');

$mesAnterior = $mes - 1; $anioAnterior = $anio;
$mesSiguiente = $mes + 1; $anioSiguiente = $anio;
if ($mesAnterior < 1) { $mesAnterior = 12; $anioAnterior--; }
if ($mesSiguiente > 12) { $mesSiguiente = 1; $anioSiguiente++; }
?>

<div class="card p-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= e(Url::to("citas?anio={$anioAnterior}&mes={$mesAnterior}")) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
            <h2 class="h5 font-display mb-0" style="min-width:220px; text-align:center;"><?= e($nombresMes[$mes]) ?> <?= $anio ?></h2>
            <a href="<?= e(Url::to("citas?anio={$anioSiguiente}&mes={$mesSiguiente}")) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= e(Url::to('citas/dia/' . date('Y-m-d'))) ?>" class="btn btn-sm btn-outline-primary">Hoy</a>
            <a href="<?= e(Url::to('citas/crear')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva cita</a>
        </div>
    </div>

    <div class="calendario-grid">
        <?php foreach ($diasSemana as $d): ?>
            <div class="calendario-cabecera"><?= $d ?></div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $offsetInicio; $i++): ?>
            <div class="calendario-celda calendario-vacia"></div>
        <?php endfor; ?>

        <?php for ($dia = 1; $dia <= $diasEnMes; $dia++):
            $fechaCelda = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            $info = $conteos[$fechaCelda] ?? null;
            $esHoy = $fechaCelda === $hoy;
        ?>
            <a href="<?= e(Url::to('citas/dia/' . $fechaCelda)) ?>" class="calendario-celda <?= $esHoy ? 'calendario-hoy' : '' ?>">
                <span class="calendario-numero"><?= $dia ?></span>
                <?php if ($info && (int) $info['activas'] > 0): ?>
                    <span class="calendario-badge"><?= (int) $info['activas'] ?> cita<?= (int) $info['activas'] === 1 ? '' : 's' ?></span>
                <?php endif; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<style>
.calendario-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
.calendario-cabecera { text-align: center; font-size: .74rem; font-weight: 700; color: var(--ink-soft); text-transform: uppercase; padding: .4rem 0; }
.calendario-celda { min-height: 78px; border: 1px solid var(--border); border-radius: .5rem; padding: .4rem; display: flex; flex-direction: column; gap: .3rem; color: var(--ink); background: var(--surface); }
.calendario-celda:hover { border-color: var(--primary); background: var(--primary-light); }
.calendario-vacia { background: transparent; border: none; }
.calendario-numero { font-family: "Lexend", sans-serif; font-weight: 600; font-size: .85rem; }
.calendario-hoy { border-color: var(--accent); border-width: 2px; }
.calendario-hoy .calendario-numero { color: var(--accent-dark); }
.calendario-badge { font-size: .68rem; background: var(--primary); color: #fff; border-radius: .3rem; padding: .1rem .4rem; align-self: flex-start; }
@media (max-width: 767.98px) { .calendario-celda { min-height: 54px; padding: .25rem; } .calendario-badge { font-size: .6rem; } }
</style>
