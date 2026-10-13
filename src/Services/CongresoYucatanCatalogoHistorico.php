<?php
declare(strict_types=1);

namespace PulsoCivico\Services;

/**
 * Catálogo del acervo histórico del H. Congreso del Estado de Yucatán.
 * Fuente: congresoyucatan.gob.mx e iepac.mx.
 */
final class CongresoYucatanCatalogoHistorico
{
    /**
     * Resumen de legislaturas recientes de Yucatán.
     * @return array<string, array<string, mixed>>
     */
    public static function getMetadataLegislaturas(): array
    {
        return [
            '60' => ['numero' => 60, 'romano' => 'LX',   'periodo' => '2012-2015', 'inicio' => '2012-09-01', 'fin' => '2015-08-31', 'total' => 25],
            '61' => ['numero' => 61, 'romano' => 'LXI',  'periodo' => '2015-2018', 'inicio' => '2015-09-01', 'fin' => '2018-08-31', 'total' => 25],
            '62' => ['numero' => 62, 'romano' => 'LXII', 'periodo' => '2018-2021', 'inicio' => '2018-09-01', 'fin' => '2021-08-31', 'total' => 25],
            '63' => ['numero' => 63, 'romano' => 'LXIII','periodo' => '2021-2024', 'inicio' => '2021-09-01', 'fin' => '2024-08-31', 'total' => 25],
            '64' => ['numero' => 64, 'romano' => 'LXIV', 'periodo' => '2024-2027', 'inicio' => '2024-09-01', 'fin' => '2027-08-31', 'total' => 35],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura63(): array
    {
        return [
            ['nombre' => 'Jesús Efrén Pérez Ballote', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => 'Álvaro Cetina Puerto'],
            ['nombre' => 'Abril Ferreyro Rosado', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => 'María Teresa Boehm Calero'],
            ['nombre' => 'Karem Faride Achach Ramírez', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => 'Alice Patrón Correa'],
            ['nombre' => 'Víctor Hugo Lozano Poveda', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => 'Marco Antonio Pasos Tec'],
            ['nombre' => 'Karla Vanessa Salazar González', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => 'Marissa Salazar Azcorra'],
            ['nombre' => 'Manuela de Jesús Cocom Bolio', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => 'Sayda Melina Rodríguez Gómez'],
            ['nombre' => 'Dafne Celina López Osorio', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => 'Zhazil Leonor Méndez'],
            ['nombre' => 'Jazmín Yaneli Villanueva Moo', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => 'Rubí Be Chan'],
            ['nombre' => 'Erik José Rihani González', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IX', 'suplente' => 'Ángel David Valdez Jiménez'],
            ['nombre' => 'Esteban Abraham Macari', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito X', 'suplente' => 'Eric Edgardo Quijano'],
            ['nombre' => 'Luis René Fernández Vidal', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XI', 'suplente' => 'Rafael Gerardo Montalvo'],
            ['nombre' => 'Esteban Goff Villanueva', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XII', 'suplente' => 'Gaspar Armando Quintal'],
            ['nombre' => 'Raúl Antonio Romero Chel', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIII', 'suplente' => 'Wilber Dzul Canul'],
            ['nombre' => 'Melba Rosana Gamboa Ávila', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIV', 'suplente' => 'Maribel del Rosario Chuc'],
            ['nombre' => 'Rafael Alejandro Echazarreta Torres', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XV', 'suplente' => 'Alba Cristina Cob'],
            // RP
            ['nombre' => 'Ingrid del Pilar Santos Díaz', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Candelaria Concepción Cobá Borja', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Alejandra de los Ángeles Novelo Segura', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Rafael Germán Quintal Medina', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Gaspar Armando Quintal Parra', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Karla Reyna Franco Blanco', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Fabiola Loeza Novelo', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Harry Gerardo Rodríguez Botello Fierro', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Eduardo Sobrino Sierra', 'partido' => 'PRD', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Vida Aravari Gómez Herrera', 'partido' => 'MC', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getLegislatura62(): array
    {
        return [
            ['nombre' => 'Paulina Aurora Viana Gómez', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito I', 'suplente' => null],
            ['nombre' => 'Víctor Merari Sánchez Roca', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito II', 'suplente' => null],
            ['nombre' => 'Miguel Edmundo Candila Noh', 'partido' => 'MORENA', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito III', 'suplente' => null],
            ['nombre' => 'Milagros Romero Bastarrachea', 'partido' => 'MC', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IV', 'suplente' => null],
            ['nombre' => 'Manuel Díaz Suárez', 'partido' => 'PAN', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito V', 'suplente' => null],
            ['nombre' => 'Marcos Nicolás Rodríguez Ruz', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VI', 'suplente' => null],
            ['nombre' => 'Felipe Cervera Hernández', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VII', 'suplente' => null],
            ['nombre' => 'Enrique Castillo Ruz', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito VIII', 'suplente' => null],
            ['nombre' => 'Lila Frías Castillo', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito IX', 'suplente' => null],
            ['nombre' => 'Mirthea del Rosario Arjona Martín', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito X', 'suplente' => null],
            ['nombre' => 'María Teresa Moisés Escalante', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XI', 'suplente' => null],
            ['nombre' => 'Janice Escobedo Salazar', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XII', 'suplente' => null],
            ['nombre' => 'Luis Enrique Borjas Romero', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIII', 'suplente' => null],
            ['nombre' => 'Karla Reyna Franco Blanco', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XIV', 'suplente' => null],
            ['nombre' => 'Warnel May Escobar', 'partido' => 'PRI', 'principio_eleccion' => 'Mayoría Relativa', 'distrito' => 'Distrito XV', 'suplente' => null],
            // RP
            ['nombre' => 'Rosa Adriana Díaz Lizama', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Kathia María Bolio Pinelo', 'partido' => 'PAN', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Lizzete Janice Escobedo Salazar', 'partido' => 'PRI', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Fátima del Rosario Perera Salazar', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Leticia Gabriela Euán Mis', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Luis Hermelindo Loeza Pacheco', 'partido' => 'MORENA', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Silvia América López Escoffié', 'partido' => 'MC', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Alejandro Cuevas Mena', 'partido' => 'PRD', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Harry Gerardo Rodríguez Botello Fierro', 'partido' => 'PVEM', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
            ['nombre' => 'Luis María Aguilar Castillo', 'partido' => 'PANAL', 'principio_eleccion' => 'Representación Proporcional', 'distrito' => null, 'suplente' => null],
        ];
    }
}
