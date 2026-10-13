<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * Catálogo histórico fundacional del H. Congreso del Estado de Baja California Sur (1974-1999).
 * Comprende el Congreso Constituyente y las Legislaturas I a VIII.
 * Fuente: Portal oficial cbcs.gob.mx (Integrantes de las Legislaturas).
 */
final class CongresoBcsCatalogoHistorico
{
    /**
     * Resumen cronológico oficial de las 18 legislaturas de BCS (1974-2027).
     * @return array<string, array<string, mixed>>
     */
    public static function getMetadataLegislaturas(): array
    {
        return [
            'constituyente' => ['numero' => 0, 'romano' => 'Constituyente', 'periodo' => '1974-1975', 'inicio' => '1974-11-25', 'fin' => '1975-01-09', 'total' => 7],
            '1'  => ['numero' => 1,  'romano' => 'I',    'periodo' => '1975-1978', 'inicio' => '1975-03-20', 'fin' => '1978-03-30', 'total' => 7],
            '2'  => ['numero' => 2,  'romano' => 'II',   'periodo' => '1978-1981', 'inicio' => '1978-04-01', 'fin' => '1981-03-30', 'total' => 8],
            '3'  => ['numero' => 3,  'romano' => 'III',  'periodo' => '1981-1984', 'inicio' => '1981-04-01', 'fin' => '1984-03-30', 'total' => 9],
            '4'  => ['numero' => 4,  'romano' => 'IV',   'periodo' => '1984-1987', 'inicio' => '1984-04-01', 'fin' => '1987-03-30', 'total' => 13],
            '5'  => ['numero' => 5,  'romano' => 'V',    'periodo' => '1987-1990', 'inicio' => '1987-04-01', 'fin' => '1990-03-30', 'total' => 12],
            '6'  => ['numero' => 6,  'romano' => 'VI',   'periodo' => '1990-1993', 'inicio' => '1990-03-15', 'fin' => '1993-03-14', 'total' => 18],
            '7'  => ['numero' => 7,  'romano' => 'VII',  'periodo' => '1993-1996', 'inicio' => '1993-03-15', 'fin' => '1996-03-14', 'total' => 15],
            '8'  => ['numero' => 8,  'romano' => 'VIII', 'periodo' => '1996-1999', 'inicio' => '1996-03-15', 'fin' => '1999-03-14', 'total' => 20],
            '9'  => ['numero' => 9,  'romano' => 'IX',   'periodo' => '1999-2002', 'inicio' => '1999-03-15', 'fin' => '2002-03-14', 'total' => 21],
            '10' => ['numero' => 10, 'romano' => 'X',    'periodo' => '2002-2005', 'inicio' => '2002-03-15', 'fin' => '2005-03-14', 'total' => 21],
            '11' => ['numero' => 11, 'romano' => 'XI',   'periodo' => '2005-2008', 'inicio' => '2005-03-15', 'fin' => '2008-03-14', 'total' => 21],
            '12' => ['numero' => 12, 'romano' => 'XII',  'periodo' => '2008-2011', 'inicio' => '2008-03-15', 'fin' => '2011-03-14', 'total' => 21],
            '13' => ['numero' => 13, 'romano' => 'XIII', 'periodo' => '2011-2015', 'inicio' => '2011-03-15', 'fin' => '2015-08-31', 'total' => 21],
            '14' => ['numero' => 14, 'romano' => 'XIV',  'periodo' => '2015-2018', 'inicio' => '2015-09-01', 'fin' => '2018-08-31', 'total' => 21],
            '15' => ['numero' => 15, 'romano' => 'XV',   'periodo' => '2018-2021', 'inicio' => '2018-09-01', 'fin' => '2021-08-31', 'total' => 21],
            '16' => ['numero' => 16, 'romano' => 'XVI',  'periodo' => '2021-2024', 'inicio' => '2021-09-01', 'fin' => '2024-08-31', 'total' => 21],
            '17' => ['numero' => 17, 'romano' => 'XVII', 'periodo' => '2024-2027', 'inicio' => '2024-09-01', 'fin' => '2027-08-31', 'total' => 21],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getConstituyente(): array
    {
        return [
            ['nombre' => 'Armando Trasviña Taylor', 'cargo' => 'Presidente', 'distrito' => 'Distrito III', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'suplente' => 'César Uribe Meza'],
            ['nombre' => 'Armando Aguilar Paniagua', 'cargo' => 'Vicepresidente', 'distrito' => 'Distrito I', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'suplente' => 'Manuel Salgado Trasviña'],
            ['nombre' => 'Eligio Soto López', 'cargo' => 'Secretario', 'distrito' => 'Distrito V', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'suplente' => 'Gilberto Morales Arce'],
            ['nombre' => 'Armando Santiesteban Cota', 'cargo' => 'Diputado', 'distrito' => 'Distrito II', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'suplente' => 'Héctor Murillo Aguilar'],
            ['nombre' => 'Fernando I. Cota Sández', 'cargo' => 'Diputado', 'distrito' => 'Distrito IV', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'suplente' => 'Jesús Castro Agúndez'],
            ['nombre' => 'Manuel Davis Ramírez', 'cargo' => 'Diputado', 'distrito' => 'Distrito VI', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'suplente' => 'José Morales Ruiz'],
            ['nombre' => 'María Luisa Salcedo Morales', 'cargo' => 'Diputada', 'distrito' => 'Distrito VII', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'suplente' => 'Ángel Salvador Ceseña'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura1(): array
    {
        return [
            ['nombre' => 'Armando Aguilar Paniagua', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => 'Ramón Ojeda Suárez'],
            ['nombre' => 'Armando Santiesteban Cota', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => 'Antonio Flores Mendoza'],
            ['nombre' => 'Armando Trasviña Taylor', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => 'Juan Manuel de la Peña'],
            ['nombre' => 'Fernando Inés Cota Sández', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => 'Julio Castillo Márquez'],
            ['nombre' => 'Eligio Soto López', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => 'Guadalupe Ontiveros Beltrán'],
            ['nombre' => 'Manuel Davis Ramírez', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => 'Manuel Martínez A.'],
            ['nombre' => 'María Luisa Salcedo de Beltrán', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => 'Gonzalo Sandoval'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura2(): array
    {
        return [
            ['nombre' => 'Antonio Flores Mendoza', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => 'Emiliano Aguilar Ruíz'],
            ['nombre' => 'Matías Amador Moyrón', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => 'Armida Torre de Caloca'],
            ['nombre' => 'Antonio Benjamín Manríquez Guluarte', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => 'César Moreno Meza'],
            ['nombre' => 'Oscar René Canseco Núñez', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => 'Juan Manuel Domínguez V.'],
            ['nombre' => 'Juan Manuel García de Jesús', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => 'Cesáreo Castro Leal'],
            ['nombre' => 'Gloria Davis de Benziger', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => 'Ignacio Armenta Salgado'],
            ['nombre' => 'Antonio Hinojosa Cabrera', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => 'Mario Suárez Salgado'],
            ['nombre' => 'Julio Pimentel Green', 'partido' => 'PPS', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => 'Francisco Javier González'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura3(): array
    {
        return [
            ['nombre' => 'Gilberto Márquez Fisher', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => 'Alejandro Andrade Martínez'],
            ['nombre' => 'María de la Luz Ramírez Ramírez', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => 'Salvador González Rubio'],
            ['nombre' => 'César Moreno Meza', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => 'Carlota Cota de Salgado'],
            ['nombre' => 'León Cota Collins', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => 'Julio Castillo Márquez'],
            ['nombre' => 'Alfonso Ledezma Alcántara', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => 'Apolinar Orozco Contreras'],
            ['nombre' => 'Alejandro Mota Vargas', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => 'Ángel R. Porras C.'],
            ['nombre' => 'Mario Vargas Aguiar', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => 'Norberto Domínguez V.'],
            ['nombre' => 'Gilberto Flores Yee', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => 'Gonzalo Sandoval M.'],
            ['nombre' => 'Manuel Ortiz Macarena', 'partido' => 'PPM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => 'Jesús Romero'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura4(): array
    {
        return [
            ['nombre' => 'Juan Manuel García García', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => 'Miguel Silva Murillo'],
            ['nombre' => 'Ignacio Izquierdo Bravo', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => 'María del Carmen Noriega'],
            ['nombre' => 'José Luis Parra Rubio', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => 'Consuelo Amador'],
            ['nombre' => 'Raúl Antonio Ortega Salgado', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => 'Ricardo Martínez M.'],
            ['nombre' => 'Miguel Antonio Olachea C.', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => 'Manuel Burgoin Castro'],
            ['nombre' => 'Juan Manuel García de Jesús', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => 'Alfonso Lizárraga N.'],
            ['nombre' => 'Benito Bermúdez Coronado', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => 'Juan Ramírez León'],
            ['nombre' => 'Arcadio Talamantes Fuerte', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => 'Gloria Davis de Benziger'],
            ['nombre' => 'María Luisa Salcedo de Beltrán', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IX', 'suplente' => 'Manuel Apodaca López'],
            ['nombre' => 'José Luis Martínez Ledezma', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito X', 'suplente' => 'Eduardo Rubio Huezo'],
            ['nombre' => 'Espiridión Sánchez López', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => 'Armando Masao Ito Larios'],
            ['nombre' => 'Luis Ruan Ruiz', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => 'Francisco Vázquez'],
            ['nombre' => 'Rubén León', 'partido' => 'PSUM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => 'Omar Anuar Pérpuly'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura5(): array
    {
        return [
            ['nombre' => 'Miguel Antonio Olachea Palacios', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => null],
            ['nombre' => 'Jesús Murillo Aguilar', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => null],
            ['nombre' => 'Rosa Isela Cota Cota', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => null],
            ['nombre' => 'Héctor Edmundo Salgado Cota', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => null],
            ['nombre' => 'Mario Vargas Aguiar', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => null],
            ['nombre' => 'Francisco Javier Carballo', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => null],
            ['nombre' => 'Víctor Manuel Lizárraga', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => null],
            ['nombre' => 'Juan Antonio Collins Morales', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => null],
            ['nombre' => 'Antonio Hinojosa Cabrera', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IX', 'suplente' => null],
            ['nombre' => 'Enrique Cárdenas González', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito X', 'suplente' => null],
            ['nombre' => 'Valente Davis Meza', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XI', 'suplente' => null],
            ['nombre' => 'Guillermo Verdugo Pérpuly', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XII', 'suplente' => null],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura6(): array
    {
        return [
            ['nombre' => 'Francisco Garibay García', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => null],
            ['nombre' => 'José Carlos Cota Osuna', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => null],
            ['nombre' => 'Jesús Ignacio Inzunza Guerrero', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => null],
            ['nombre' => 'Benito Murillo Aguilar', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => null],
            ['nombre' => 'Álvaro Gerardo Higuera', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => null],
            ['nombre' => 'Emiliano Aguilar Ruiz', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => null],
            ['nombre' => 'Franco Domínguez Verduzco', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => null],
            ['nombre' => 'Miguel Antonio Olachea Palacios', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => null],
            ['nombre' => 'Aloys Aguilar Preisser', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IX', 'suplente' => null],
            ['nombre' => 'Gonzalo Verdugo Pérpuly', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito X', 'suplente' => null],
            ['nombre' => 'Mario Amador Moyrón', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XI', 'suplente' => null],
            ['nombre' => 'Juan Adolfo Orantes', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XII', 'suplente' => null],
            ['nombre' => 'René Holmos Lucero', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIII', 'suplente' => null],
            ['nombre' => 'Guillermo Santillán Meza', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIV', 'suplente' => null],
            ['nombre' => 'Ramón Salgado Amador', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XV', 'suplente' => null],
            ['nombre' => 'Héctor Manuel Montaño Cota', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Jesús Murillo Aguilar', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Ramón Ojeda Matus', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura7(): array
    {
        return [
            ['nombre' => 'César de Jesús Ortega Salgado', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => null],
            ['nombre' => 'Amadeo Murillo Aguilar', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => null],
            ['nombre' => 'José Carlos Cota Osuna', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => null],
            ['nombre' => 'Jesús Castro Agúndez', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => null],
            ['nombre' => 'Juan Antonio Collins Morales', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => null],
            ['nombre' => 'Mario Amador Moyrón', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => null],
            ['nombre' => 'Antonio Hinojosa Cabrera', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => null],
            ['nombre' => 'Valentín Castro Burgoin', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => null],
            ['nombre' => 'Francisco Javier Obregón Espinoza', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IX', 'suplente' => null],
            ['nombre' => 'Jesús Cesáreo Sánchez López', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito X', 'suplente' => null],
            ['nombre' => 'Humberto Barrera Navarro', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XI', 'suplente' => null],
            ['nombre' => 'Ramiro Feerman Davis', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XII', 'suplente' => null],
            ['nombre' => 'María de Lourdes Orduño Ortiz', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIII', 'suplente' => null],
            ['nombre' => 'Paulino Molina Romero', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIV', 'suplente' => null],
            ['nombre' => 'Manuel S. Salgado Amador', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XV', 'suplente' => null],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura8(): array
    {
        return [
            ['nombre' => 'Reyes Barrón Bustamante', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => 'Inés María Sánchez González'],
            ['nombre' => 'Valentín Castro Burgoin', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => 'Mario Olvera Moreno'],
            ['nombre' => 'Valentín de J. Salgado Cota', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => 'Sofía Sui Qui García'],
            ['nombre' => 'Paulino Molina Romero', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => 'Siria Verdugo Davis'],
            ['nombre' => 'César de J. Ortega Salgado', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => 'Jorge Ramírez Martínez'],
            ['nombre' => 'Manuel S. Salgado Amador', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => 'Sergio A. Aguilar Martínez'],
            ['nombre' => 'Ángel S. Ceseña Burgoin', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => 'Jorge Arturo Cota Salgado'],
            ['nombre' => 'María de Lourdes Orduño Ortiz', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => 'Sara Echeverría Rodríguez'],
            ['nombre' => 'Francisco Javier Obregón Espinoza', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IX', 'suplente' => 'Oscar Vicente López Beltrán'],
            ['nombre' => 'Jesús Cesáreo Sánchez López', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito X', 'suplente' => 'Alfonso Herrera Márquez'],
            ['nombre' => 'María Juana Hernández Paularena', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XI', 'suplente' => 'Lorenzo Cázares Duarte'],
            ['nombre' => 'Ramiro Feerman Davis', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XII', 'suplente' => 'Guadalupe Martínez Murillo'],
            ['nombre' => 'Humberto Barrera Navarro', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIII', 'suplente' => 'Martha Alicia Mayoral Mayoral'],
            ['nombre' => 'Jorge Alberto Cachú Ruiz', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIV', 'suplente' => 'Julio Enríquez Fuentes'],
            ['nombre' => 'Jesús Redona Murillo', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XV', 'suplente' => 'Felipe Reyes Amador'],
            ['nombre' => 'Rodolfo Davis Osuna', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Rigoberto Mares Aguilar', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Mercedes Maciel Ortiz', 'partido' => 'PT', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Pedro Osorio', 'partido' => 'PT', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Rosa Delia Cota Montaño', 'partido' => 'PRD', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
        ];
    }
}
