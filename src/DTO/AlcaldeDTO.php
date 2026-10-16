<?php
declare(strict_types=1);

namespace PulsoCivico\DTO;

/**
 * Data Transfer Object que representa a un Alcalde o Presidente Municipal,
 * tanto en funciones (directorio vigente) como en series históricas.
 */
class AlcaldeDTO
{
    public function __construct(
        public readonly string $cvegeo,
        public readonly string $entidad,
        public readonly int $cveEnt,
        public readonly string $municipio,
        public readonly int $cveMun,
        public readonly string $nombreCompleto,
        public readonly string $periodo,
        public readonly int $anoInicio,
        public readonly int $anoFin,
        public readonly ?string $partido = null,
        public readonly bool $esVigente = false,
        public readonly ?string $genero = null,
        public readonly ?bool $reeleccion = null,
        public readonly ?float $margenVictoria = null,
        public readonly ?string $segundoLugar = null,
        public readonly ?string $partidoSegundoLugar = null,
        public readonly ?string $tipoCargo = 'Presidente Municipal Constitucional',
        public readonly ?string $notas = null,
        public readonly ?string $fuenteUrl = null,
        public readonly ?string $fechaExtraccion = null
    ) {
    }

    /**
     * Serializa el DTO a un arreglo compatible con el estándar JSON de Pulso Cívico.
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'cvegeo' => $this->cvegeo,
            'entidad' => $this->entidad,
            'cve_ent' => $this->cveEnt,
            'municipio' => $this->municipio,
            'cve_mun' => $this->cveMun,
            'nombre_completo' => $this->nombreCompleto,
            'periodo' => $this->periodo,
            'ano_inicio' => $this->anoInicio,
            'ano_fin' => $this->anoFin,
            'partido' => $this->partido,
            'es_vigente' => $this->esVigente,
            'genero' => $this->genero,
            'reeleccion' => $this->reeleccion,
            'margen_victoria' => $this->margenVictoria,
            'segundo_lugar' => $this->segundoLugar,
            'partido_segundo_lugar' => $this->partidoSegundoLugar,
            'tipo_cargo' => $this->tipoCargo,
            'notas' => $this->notas,
            'metadata' => [
                'fuente_url' => $this->fuenteUrl ?? 'https://www.gob.mx/inafed',
                'fecha_extraccion' => $this->fechaExtraccion ?? date('Y-m-d'),
            ],
        ];
    }
}
