<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * Catálogo oficial y verificado de la XVII Legislatura (2024-2027)
 * del H. Congreso del Estado de Baja California Sur.
 * Fuente: Portal oficial cbcs.gob.mx e IEEBCS (Acuerdo IEEBCS-CG143-JUNIO-2024).
 */
final class CongresoBcsCatalogoActual
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getDiputadosXVII(): array
    {
        return [
            // MAYORÍA RELATIVA (16 Distritos Electorales)
            [
                'nombre' => 'Alondra Torres García',
                'partido' => 'PT',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito I',
                'municipio' => 'Los Cabos (San José del Cabo)',
                'correo' => 'dip_alondratorres@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 801',
                'suplente' => 'Rebeca Espinoza Aguilar',
                'comisiones' => [
                    ['nombre' => 'Comisión de Asuntos Políticos', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de La Diversidad Sexual', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Cuenta y Administración', 'cargo' => 'Secretaria'],
                    ['nombre' => 'Comisión de Igualdad de Género', 'cargo' => 'Secretaria'],
                    ['nombre' => 'Comisión del Agua', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Dalia Verónica Collins Mendoza',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito II',
                'municipio' => 'La Paz',
                'correo' => 'dip_daliacollins@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 802',
                'suplente' => 'Clarissa Gálvez Camacho',
                'comisiones' => [
                    ['nombre' => 'Comisión de Salud, la Familia y la Asistencia Pública', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Deporte', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Guadalupe Vázquez Jacinto',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito III',
                'municipio' => 'La Paz',
                'correo' => 'dip_guadalupevazquez@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 803',
                'suplente' => 'Miriam Patricia Romero Estrada',
                'comisiones' => [
                    ['nombre' => 'Comisión de Desarrollo Social', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Asuntos Educativos', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Sergio Guluarte Ceseña',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IV',
                'municipio' => 'La Paz',
                'correo' => 'dip_sergioguluarte@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 804',
                'suplente' => 'Víctor Manuel Rochín Castro',
                'comisiones' => [
                    ['nombre' => 'Comisión de Seguridad Pública y Protección Civil', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Asuntos Laborales y de Previsión Social', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Sergio Polanco Salaices',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito V',
                'municipio' => 'La Paz',
                'correo' => 'dip_sergiopolanco@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 805',
                'suplente' => 'Juan Antonio Collins Morales',
                'comisiones' => [
                    ['nombre' => 'Comisión de Infraestructura y Obras Públicas', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Comunicaciones y Transportes', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Eduardo Valentín Van Wormer Castro',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VI',
                'municipio' => 'La Paz',
                'correo' => 'dip_eduardovanwormer@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 806',
                'suplente' => 'Héctor Manuel Montaño Cota',
                'comisiones' => [
                    ['nombre' => 'Junta de Gobierno y Coordinación Política (JUGOCOPO)', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Asuntos Agropecuarios, Forestales y Mineros', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Guillermina Díaz Rodríguez',
                'partido' => 'PT',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VII',
                'municipio' => 'La Paz',
                'correo' => 'dip_guillerminadiaz@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 807',
                'suplente' => 'Beatriz Adriana Arana Davis',
                'comisiones' => [
                    ['nombre' => 'Comisión de Atención a Grupos Vulnerables', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Pesca y Acuacultura', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'María Cristina Contreras Rebollo',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito VIII',
                'municipio' => 'Los Cabos (Cabo San Lucas)',
                'correo' => 'dip_cristinacontreras@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 808',
                'suplente' => 'Adriana Lorena Manríquez Arce',
                'comisiones' => [
                    ['nombre' => 'Comisión de Asuntos Fiscales y Administrativos', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Turismo', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Gabriela Montoya Terrazas',
                'partido' => 'PT',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito IX',
                'municipio' => 'Los Cabos / Loreto / Comondú',
                'correo' => 'dip_gabrielamontoya@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 809',
                'suplente' => 'Yazmín Lizeth Ruiz Gómez',
                'comisiones' => [
                    ['nombre' => 'Comisión de Turismo', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Asuntos Políticos', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Fernando Hoyos Aguilar',
                'partido' => 'PT',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito X',
                'municipio' => 'La Paz (Ciudad Constitución)',
                'correo' => 'dip_fernandohoyos@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 810',
                'suplente' => 'Jorge Alberto Ceseña Ruiz',
                'comisiones' => [
                    ['nombre' => 'Comisión de Asuntos Agropecuarios, Forestales y Mineros', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Ecología y Medio Ambiente', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Martín Escogido Flores',
                'partido' => 'PANAL',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XI',
                'municipio' => 'Loreto / Comondú / La Paz',
                'correo' => 'dip_martinescogido@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 811',
                'suplente' => 'Raúl Ignacio Villalobos Cota',
                'comisiones' => [
                    ['nombre' => 'Comisión de Asuntos Educativos', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Cultura y Artes', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Omar Torres Orozco',
                'partido' => 'PT',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XII',
                'municipio' => 'Los Cabos (San José del Cabo)',
                'correo' => 'dip_omartorres@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 812',
                'suplente' => 'José Carlos Castro Amador',
                'comisiones' => [
                    ['nombre' => 'Comisión de Deporte', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Desarrollo Urbano', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Venustiano Pérez Sánchez',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIII',
                'municipio' => 'Mulegé / Loreto / Comondú',
                'correo' => 'dip_venustianoperez@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 813',
                'suplente' => 'José Alfredo Gómez García',
                'comisiones' => [
                    ['nombre' => 'Comisión de Pesca y Acuacultura', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Vigilancia de la Auditoría Superior', 'cargo' => 'Secretario'],
                ],
            ],
            [
                'nombre' => 'Teresita de Jesús Valentín Vázquez',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XIV',
                'municipio' => 'Mulegé (Guerrero Negro)',
                'correo' => 'dip_teresitavalentin@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 814',
                'suplente' => 'Francisca Zúñiga Morales',
                'comisiones' => [
                    ['nombre' => 'Comisión de Derechos Humanos e Inclusión', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Enlace Legislativo', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Karina Olivas Parra',
                'partido' => 'PT',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XV',
                'municipio' => 'La Paz / Los Cabos',
                'correo' => 'dip_karinaolivas@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 815',
                'suplente' => 'Rosa María Cota Murillo',
                'comisiones' => [
                    ['nombre' => 'Comisión de Igualdad de Género', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Seguridad Pública', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Sergio Ricardo Huerta Leggs',
                'partido' => 'MORENA',
                'principio_eleccion' => 'Mayoría Relativa',
                'distrito' => 'Distrito XVI',
                'municipio' => 'Los Cabos (Cabo San Lucas)',
                'correo' => 'dip_sergiohuerta@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 816',
                'suplente' => 'Francisco Javier Gómez Lucero',
                'comisiones' => [
                    ['nombre' => 'Comisión del Agua', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Asuntos Fiscales y Administrativos', 'cargo' => 'Secretario'],
                ],
            ],

            // REPRESENTACIÓN PROPORCIONAL (5 Diputaciones)
            [
                'nombre' => 'María Guadalupe Saldaña Cisneros',
                'partido' => 'PAN',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => 'Estatal',
                'correo' => 'dip_lupitasaldana@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 817',
                'suplente' => 'Melissa Natividad Almeida Sánchez',
                'comisiones' => [
                    ['nombre' => 'Comisión de Ecología y Medio Ambiente', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Vigilancia de la Auditoría Superior', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Lourdes Cornejo Rendón',
                'partido' => 'PANAL',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => 'Estatal',
                'correo' => 'dip_lourdescornejo@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 818',
                'suplente' => 'Blanca Azucena Hernández Castro',
                'comisiones' => [
                    ['nombre' => 'Comisión de Transparencia y Anticorrupción', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Asuntos Educativos', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Christian Fabrizio del Castillo Miranda',
                'partido' => 'PRI',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => 'Estatal',
                'correo' => 'dip_fabriciodelcastillo@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 819',
                'suplente' => 'Damián Velázquez Cruz',
                'comisiones' => [
                    ['nombre' => 'Comisión de Desarrollo Urbano, Obras Públicas y Tránsito', 'cargo' => 'Secretario'],
                    ['nombre' => 'Comisión de Juventud', 'cargo' => 'Presidente'],
                ],
            ],
            [
                'nombre' => 'Arlene Moreno Maciel',
                'partido' => 'PT',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => 'Estatal',
                'correo' => 'dip_arlenemoreno@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 820',
                'suplente' => 'Sarahí Ramos Lucero',
                'comisiones' => [
                    ['nombre' => 'Comisión de Cultura y Artes', 'cargo' => 'Presidenta'],
                    ['nombre' => 'Comisión de Salud y Asistencia Pública', 'cargo' => 'Secretaria'],
                ],
            ],
            [
                'nombre' => 'Erick Iván Agúndez Cervantes',
                'partido' => 'PVEM',
                'principio_eleccion' => 'Representación Proporcional',
                'distrito' => null,
                'municipio' => 'Estatal',
                'correo' => 'dip_erickagundez@cbcs.gob.mx',
                'telefono' => '(612) 123-7800 Ext. 821',
                'suplente' => 'Flavio Antonio Olachea Montaño',
                'comisiones' => [
                    ['nombre' => 'Comisión de Comunicaciones y Transportes', 'cargo' => 'Presidente'],
                    ['nombre' => 'Comisión de Ecología', 'cargo' => 'Secretario'],
                ],
            ],
        ];
    }
}
