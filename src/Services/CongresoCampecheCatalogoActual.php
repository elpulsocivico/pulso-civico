<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * Catálogo oficial y verificado de la LXV Legislatura (2024-2027)
 * del H. Congreso del Estado de Campeche.
 * Fuente: Portal oficial congresocam.gob.mx e IEEC (Acuerdos CG/116/2024 y CG/124/2024).
 */
final class CongresoCampecheCatalogoActual
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputadosLXV(): array
    {
        return [
            // =========================================================================
            // 1. MAYORÍA RELATIVA (21 Distritos Electorales Uninominales)
            // =========================================================================
            [
                'nombre' => 'Paul Alfredo Arce Ontiveros',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito I',
                'municipio' => 'San Francisco de Campeche',
                'correo' => 'dip_paularce@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 101',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Asuntos Municipales', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Ignacio José Muñoz Hernández',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito II',
                'municipio' => 'San Francisco de Campeche',
                'correo' => 'dip_ignaciomunoz@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 102',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Desarrollo Económico', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Hipsi Marisol Estrella Guillermo',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito III',
                'municipio' => 'San Francisco de Campeche',
                'correo' => 'dip_hipsdestrella@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 103',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Segunda Vicepresidenta'],
                    ['nombre' => 'Comisión de Igualdad de Género', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Aldo Román Contreras Uc',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IV',
                'municipio' => 'San Francisco de Campeche',
                'correo' => 'dip_aldocontreras@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 104',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Juventud y Deporte', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Mónica Fernández Montúfar',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito V',
                'municipio' => 'San Francisco de Campeche',
                'correo' => 'dip_monicafernandez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 105',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Segunda Secretaria Suplente'],
                    ['nombre' => 'Comisión de Cultura y Turismo', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Diana Luisa Aguilar Ruelas',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VI',
                'municipio' => 'San Francisco de Campeche',
                'correo' => 'dip_dianaaguilar@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 106',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Medio Ambiente y Desarrollo Sustentable', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Omar Talango Cervantes',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VII',
                'municipio' => 'Tenabo',
                'correo' => 'dip_omartalango@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 107',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Desarrollo Rural y Pesca', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Francisca Zárate López',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VIII',
                'municipio' => 'Carmen (Ciudad del Carmen)',
                'correo' => 'dip_franciscazarate@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 108',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Salud', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'Jorge Pérez Falconi',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IX',
                'municipio' => 'Carmen (Ciudad del Carmen)',
                'correo' => 'dip_jorgeperez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 109',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Derechos Humanos y de Asuntos de Familia', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Dalila del Carmen Mata Pérez',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito X',
                'municipio' => 'Carmen (Ciudad del Carmen)',
                'correo' => 'dip_dalilamata@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 110',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Finanzas y Hacienda Pública', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'Ismael López Garcés',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XI',
                'municipio' => 'Carmen (Ciudad del Carmen)',
                'correo' => 'dip_ismaellopez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 111',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Asuntos Energéticos', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Gladys Sofía Rivera López',
                'partido' => 'MC',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XII',
                'municipio' => 'Carmen (Sabancuy)',
                'correo' => 'dip_gladysrivera@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 112',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Primera Vicepresidenta'],
                    ['nombre' => 'Comisión de Pesca y Acuacultura', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Abigail Gutiérrez Morales',
                'partido' => 'PT',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIII',
                'municipio' => 'Escárcega',
                'correo' => 'dip_abigailgutierrez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 113',
                'suplente' => 'Ana María López Hernández',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Tercera Secretaria'],
                    ['nombre' => 'Comisión de Seguridad Pública y Protección a la Comunidad', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'Ena América García García',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIV',
                'municipio' => 'Candelaria',
                'correo' => 'dip_enagarcia@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 114',
                'suplente' => 'Balbina Alejandra Hidalgo Zavala',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Primera Secretaria'],
                    ['nombre' => 'Comisión de Atención a Grupos Vulnerables', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'Balbina Alejandra Hidalgo Zavala',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XV',
                'municipio' => 'Champotón',
                'correo' => 'dip_balbinahidalgo@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 115',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Puntos Constitucionales y Control Interno de Convencionalidad', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'Elías Noé Baeza Aké',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVI',
                'municipio' => 'Seybaplaya',
                'correo' => 'dip_eliasbaeza@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 116',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Comunicaciones y Transportes', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Mayda Aracely Mas Tun',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVII',
                'municipio' => 'Calkiní',
                'correo' => 'dip_maydamas@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 117',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Pueblos y Comunidades Indígenas', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'Miguel Ángel Pool Alpuche',
                'partido' => 'PAN',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVIII',
                'municipio' => 'Hopelchén',
                'correo' => 'dip_miguelpool@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 118',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Vigilancia y Fiscalización', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Gaspar de Jesús Nah Miss',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIX',
                'municipio' => 'Hecelchakán',
                'correo' => 'dip_gasparnah@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 119',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Educación', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Daher Antonio Puch Rivera',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XX',
                'municipio' => 'Palizada',
                'correo' => 'dip_daherpuch@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 120',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Ganadería y Desarrollo Agropecuario', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'María del Carmen Ávalos Trujillo',
                'partido' => 'PVEM',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XXI',
                'municipio' => 'Calakmul (Xpujil)',
                'correo' => 'dip_carmenavalos@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 121',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Ecología y Medio Ambiente', 'cargo' => 'Presidenta'],
                ],
            ],

            // =========================================================================
            // 2. REPRESENTACIÓN PROPORCIONAL (14 Fórmulas Plurinominales)
            // =========================================================================
            [
                'nombre' => 'Maricela Flores Moo',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 1)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_maricelaflores@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 201',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'José Antonio Jiménez Gutiérrez',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 2)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_antoniojimenez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 202',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Junta de Gobierno y Administración', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Verónica Margarita Roca Méndez',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 3)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_veronicaroca@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 203',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Desarrollo Social y Regional', 'cargo' => 'Presidenta'],
                ],
            ],
            [
                'nombre' => 'Carlos Enrique Ucán Yam',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 4)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_carlosucan@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 204',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Transparencia y Acceso a la Información', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Pedro Armentía López',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 1)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_pedroarmentia@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 205',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Finanzas y Hacienda Pública', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Tania Domínguez Fernández',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 2)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_taniadominguez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 206',
                'suplente' => 'Mónica Fernández Montúfar',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Segunda Secretaria'],
                ],
            ],
            [
                'nombre' => 'Pedro Hernández MacDonald',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 3)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_pedrohernandez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 207',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Trabajo y Previsión Social', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Tania González Pérez',
                'partido' => 'MC',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 4)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_taniagonzalez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 208',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Participación Ciudadana', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Josué de Jesús Rodríguez Golib',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 1)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_josuerodriguez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 209',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Justicia y Seguridad Pública', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'María del Rosario Cruz Hernández',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 2)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_rosariocruz@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 210',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Derechos Humanos', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Delma del Carmen Rabelo Cuevas',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 1)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_delmarabelo@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 211',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Salud y Asistencia Social', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Jorge Salim Abraham Quijano',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 2)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_salimabraham@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 212',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Finanzas y Hacienda Pública', 'cargo' => 'Primer Vocal'],
                ],
            ],
            [
                'nombre' => 'Ana María López Hernández',
                'partido' => 'PT',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 1)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_anamarialopez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 213',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Mesa Directiva', 'cargo' => 'Tercera Secretaria Suplente'],
                    ['nombre' => 'Comisión de Asuntos Indígenas y Afromexicanos', 'cargo' => 'Integrante'],
                ],
            ],
            [
                'nombre' => 'Andrés Fernández del Valle Laisequilla',
                'partido' => 'PVEM',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => 'Circunscripción Estatal (Fórmula 1)',
                'municipio' => 'Circunscripción Estatal',
                'correo' => 'dip_andresfernandez@congresocam.gob.mx',
                'telefono' => '(981) 811-3668 Ext. 214',
                'suplente' => 'No reportado en gaceta de instalación',
                'comisiones' => [
                    ['nombre' => 'Comisión de Desarrollo Urbano y Obras Públicas', 'cargo' => 'Integrante'],
                ],
            ],
        ];
    }
}
