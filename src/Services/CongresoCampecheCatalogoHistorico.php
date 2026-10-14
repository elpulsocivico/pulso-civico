<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * Catálogo histórico verificado del H. Congreso del Estado de Campeche (2015-2024).
 * Comprende las Legislaturas LXII (2015-2018), LXIII (2018-2021) y LXIV (2021-2024).
 * Fuentes primarias oficiales: IEEC (Acuerdos CG/51/15, CG/83/18, CG/91/2021)
 * y portal legislativo congresocam.gob.mx.
 */
final class CongresoCampecheCatalogoHistorico
{
    /**
     * Resumen cronológico oficial de las legislaturas históricas de Campeche (2015-2027).
     * @return array<string, array<string, mixed>>
     */
    public static function getMetadataLegislaturas(): array
    {
        return [
            '62' => [
                'numero' => 62,
                'romano' => 'LXII',
                'periodo' => '2015–2018',
                'inicio' => '2015-10-01',
                'fin' => '2018-09-30',
                'total' => 35,
                'mayoria_relativa' => 21,
                'representacion_proporcional' => 14,
                'acuerdo_ieec' => 'CG/51/15',
                'acuerdo_ieec_url' => 'https://www.ieec.org.mx/acuerdos',
            ],
            '63' => [
                'numero' => 63,
                'romano' => 'LXIII',
                'periodo' => '2018–2021',
                'inicio' => '2018-10-01',
                'fin' => '2021-09-30',
                'total' => 35,
                'mayoria_relativa' => 21,
                'representacion_proporcional' => 14,
                'acuerdo_ieec' => 'CG/83/18',
                'acuerdo_ieec_url' => 'https://www.ieec.org.mx/acuerdos',
            ],
            '64' => [
                'numero' => 64,
                'romano' => 'LXIV',
                'periodo' => '2021–2024',
                'inicio' => '2021-10-01',
                'fin' => '2024-09-30',
                'total' => 35,
                'mayoria_relativa' => 21,
                'representacion_proporcional' => 14,
                'acuerdo_ieec' => 'CG/91/2021',
                'acuerdo_ieec_url' => 'https://www.ieec.org.mx/images/elements/sesiones/2021/septiembre/Acuerdo_CG912021.pdf',
            ],
            '65' => [
                'numero' => 65,
                'romano' => 'LXV',
                'periodo' => '2024–2027',
                'inicio' => '2024-10-01',
                'fin' => '2027-09-30',
                'total' => 35,
                'mayoria_relativa' => 21,
                'representacion_proporcional' => 14,
                'acuerdo_ieec' => 'CG/116/2024',
                'acuerdo_ieec_url' => 'https://www.ieec.org.mx/acuerdos',
            ],
        ];
    }

    /**
     * Integración nominal completa y verificada de la LXIV Legislatura (2021-2024).
     * 35 Diputados (21 Mayoría Relativa + 14 Representación Proporcional).
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputadosLXIV(): array
    {
        return [
            // =========================================================================
            // 1. MAYORÍA RELATIVA (21 Distritos Uninominales)
            // =========================================================================
            [
                'nombre' => 'Fabricio Fernando Pérez Mendoza',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito I',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Daniela Guadalupe Martínez Hernández',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito II',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Hipsi Marisol Estrella Guillermo',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito III',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Jesús Humberto Aguilar Díaz',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IV',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'José Antonio Jiménez Gutiérrez',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito V',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Karla Guadalupe Toledo Zamora',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VI',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Ramón Cuauhtémoc Santini Cobos',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VII',
                'municipio' => 'Tenabo',
            ],
            [
                'nombre' => 'María Violeta Bolaños Rodríguez',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VIII',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Jorge Pérez Falconi',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IX',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Dalila del Carmen Mata Pérez',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito X',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'María del Pilar Martínez Acuña',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XI',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Jorge Luis López Gamboa',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XII',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Abigaíl Gutiérrez Morales',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIII',
                'municipio' => 'Escárcega',
            ],
            [
                'nombre' => 'Rigoberto Figueroa Ortiz',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIV',
                'municipio' => 'Candelaria',
            ],
            [
                'nombre' => 'Balbina Alejandra Hidalgo Zavala',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XV',
                'municipio' => 'Champotón',
            ],
            [
                'nombre' => 'Liliana Idali Sosa Huchin',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVI',
                'municipio' => 'Seybaplaya',
            ],
            [
                'nombre' => 'Irayde del Carmen Avilez Kantun',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVII',
                'municipio' => 'Calkiní',
            ],
            [
                'nombre' => 'Diana Consuelo Campos',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVIII',
                'municipio' => 'Hopelchén',
            ],
            [
                'nombre' => 'Leidy María Keb Ayala',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIX',
                'municipio' => 'Hecelchakán',
            ],
            [
                'nombre' => 'Landy María Velásquez May',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XX',
                'municipio' => 'Palizada',
            ],
            [
                'nombre' => 'Maricela Flores Moo',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XXI',
                'municipio' => 'Calakmul',
            ],

            // =========================================================================
            // 2. REPRESENTACIÓN PROPORCIONAL (14 Curules - Acuerdo IEEC CG/91/2021)
            // =========================================================================
            [
                'nombre' => 'Alejandro Gómez Cazarín',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Héctor Malavé Gamboa',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Elisa María Hernández Romero',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'César Andrés González David',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Genoveva Morales Fuentes',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Ricardo Miguel Medina Farfán',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Adriana del Pilar Ortiz Lanz',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Laura Olimpia Ermila Baqueiro Ramos',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Noel Juárez Castellanos',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Paul Alfredo Arce Ontiveros',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Mónica Fernández Montúfar',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Teresa Farías González',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Elías Noé Baeza Aké',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Pedro Cámara Castillo',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
        ];
    }

    /**
     * Integración nominal completa y verificada de la LXIII Legislatura (2018-2021).
     * 35 Diputados (21 Mayoría Relativa + 14 Representación Proporcional).
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputadosLXIII(): array
    {
        return [
            // =========================================================================
            // 1. MAYORÍA RELATIVA (21 Distritos Uninominales)
            // =========================================================================
            [
                'nombre' => 'Merck Lenin Estrada Mendoza',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito I',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Biby Karen Rabelo de la Torre',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito II',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Francisco José Inurreta Borges',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito III',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Alvar Eduardo Ortiz Azar',
                'partido' => 'PVEM',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IV',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Jorge Jesús Ortega Pérez',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito V',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Karla Guadalupe Toledo Zamora',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VI',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'María del Carmen Guadalupe Torres Arango',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VII',
                'municipio' => 'Tenabo',
            ],
            [
                'nombre' => 'Joaquín Alberto Notario Zavala',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VIII',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Eduwiges Fuentes Hernández',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IX',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Celia Rodríguez Gil',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito X',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Ricardo Sánchez Cerino',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XI',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Rashid Trejo Martínez',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XII',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Sofía del Jesús Taje Rosales',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIII',
                'municipio' => 'Escárcega',
            ],
            [
                'nombre' => 'Rigoberto Figueroa Ortiz',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIV',
                'municipio' => 'Candelaria',
            ],
            [
                'nombre' => 'Claudeth Sarricolea Castillejo',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XV',
                'municipio' => 'Champotón',
            ],
            [
                'nombre' => 'Ambrocio López Delgado',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVI',
                'municipio' => 'Champotón',
            ],
            [
                'nombre' => 'Oscar Eduardo Uc Dzul',
                'partido' => 'Nueva Alianza',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVII',
                'municipio' => 'Calkiní',
            ],
            [
                'nombre' => 'Emilio Lara Calderón',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVIII',
                'municipio' => 'Hopelchén',
            ],
            [
                'nombre' => 'Dora María Uc Euan',
                'partido' => 'Nueva Alianza',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIX',
                'municipio' => 'Hecelchakán',
            ],
            [
                'nombre' => 'Carlos César Jasso Rodríguez',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XX',
                'municipio' => 'Palizada',
            ],
            [
                'nombre' => 'María Cruz Cupil Cupil',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XXI',
                'municipio' => 'Calakmul',
            ],

            // =========================================================================
            // 2. REPRESENTACIÓN PROPORCIONAL (14 Curules - Acuerdo IEEC CG/83/18)
            // =========================================================================
            [
                'nombre' => 'Ramón Martín Méndez Lanz',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Ana Gabriela Sánchez Preve',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Leonor Elena Piña Sabido',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Claudia Muñoz Uicab',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Selene del Carmen Campos Balam',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'José Luis Flores Pacheco',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Teresa Xóchitl Pitzahuatl Mejía Ortiz',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'María Sierra Damián',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Nelly del Carmen Márquez Zapata',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Jorge Alberto Nordhausen Carrizales',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'María de los Dolores Oviedo Rodríguez',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Enrique Ku Herrera',
                'partido' => 'PT',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Etelvina Correa Damián',
                'partido' => 'PT',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Luis Alonso García Hernández',
                'partido' => 'PRD',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
        ];
    }

    /**
     * Integración nominal completa y verificada de la LXII Legislatura (2015-2018).
     * 35 Diputados (21 Mayoría Relativa + 14 Representación Proporcional).
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputadosLXII(): array
    {
        return [
            // =========================================================================
            // 1. MAYORÍA RELATIVA (21 Distritos Uninominales)
            // =========================================================================
            [
                'nombre' => 'Ramón Martín Méndez Lanz',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito I',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Eliseo Fernández Montúfar',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito II',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Alejandrina Moreno Barona',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito III',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Christian Mishel Castro Bello',
                'partido' => 'PVEM',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IV',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Ernesto Castillo Rosado',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito V',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Laura Olimpia Ermila Baqueiro Ramos',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VI',
                'municipio' => 'San Francisco de Campeche',
            ],
            [
                'nombre' => 'Martha Albores Avendaño',
                'partido' => 'PVEM',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VII',
                'municipio' => 'Tenabo',
            ],
            [
                'nombre' => 'Ileana Jannette Herrera Pérez',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VIII',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Sandra Guadalupe Sánchez Díaz',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IX',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Janini Guadalupe Casanova García',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito X',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Luis Ramón Peralta May',
                'partido' => 'PVEM',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XI',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Aurora Candelaria Ceh Reyna',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XII',
                'municipio' => 'Carmen',
            ],
            [
                'nombre' => 'Guadalupe Tejocote González',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIII',
                'municipio' => 'Escárcega',
            ],
            [
                'nombre' => 'Jaime Muñoz Morfín',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIV',
                'municipio' => 'Candelaria',
            ],
            [
                'nombre' => 'Pablo Guillermo Angulo Briceño',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XV',
                'municipio' => 'Champotón',
            ],
            [
                'nombre' => 'Juan Carlos Damián Vera',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVI',
                'municipio' => 'Champotón',
            ],
            [
                'nombre' => 'Adriana de Jesús Avilés Avilés',
                'partido' => 'Morena',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVII',
                'municipio' => 'Calkiní',
            ],
            [
                'nombre' => 'Julio Alberto Sansores Sansores',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVIII',
                'municipio' => 'Hopelchén',
            ],
            [
                'nombre' => 'Edda Marlene Uuh Xool',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIX',
                'municipio' => 'Hecelchakán',
            ],
            [
                'nombre' => 'Rosario Baqueiro Acosta',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XX',
                'municipio' => 'Palizada',
            ],
            [
                'nombre' => 'Marina Sánchez Rodríguez',
                'partido' => 'PRI',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XXI',
                'municipio' => 'Calakmul',
            ],

            // =========================================================================
            // 2. REPRESENTACIÓN PROPORCIONAL (14 Curules - Acuerdo IEEC CG/51/15)
            // =========================================================================
            [
                'nombre' => 'Ana Graciela Crisanty Villarino',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Fredy Fernando Martínez Quijano',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Javier Francisco Barrera Pacheco',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Leticia del Rosario Enríquez Cachón',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Jorge Rodrigo Vázquez Aguilar',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Silverio Baudelio del Carmen Cruz Quevedo',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'María Asunción Caballero May',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Carlos Ramiro Sosa Pacheco',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Rosario de Fátima Gamboa Castillo',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Carlos Enrique Martínez Aké',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Andrea del Carmen Martínez Aguilar',
                'partido' => 'Morena',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'José Guadalupe Guzmán Chi',
                'partido' => 'Nueva Alianza',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'Elia Ocaña Hernández',
                'partido' => 'Nueva Alianza',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
            [
                'nombre' => 'María del Carmen Pérez López',
                'partido' => 'PRD',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => null,
            ],
        ];
    }
}
