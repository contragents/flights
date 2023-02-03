<?php

class TestFlights
{
    const TEST_FLIGHTS = [
        [
            'from' => 'VKO',
            'to' => 'DME',
            'depart' => '01.01.2020 12:44',
            'arrival' => '01.01.2020 13:44',
        ],
        [
            'from' => 'DME',
            'to' => 'JFK',
            'depart' => '02.01.2020 23:00',
            'arrival' => '03.01.2020 11:44',
        ],
        [
            'from' => 'DME',
            'to' => 'HKT',
            'depart' => '01.01.2020 13:40',
            'arrival' => '01.01.2020 22:22',
        ],
    ];

    private $sortedFlights = [];
    private $routes = [];

    /*
    Формат массива routes
    ['0'=>[
    'duration' => 45000,
    'from' => 'DME',
    'to' => 'JFK',
    'depart' => '02.01.2020 23:00',
    'arrival' => '03.01.2020 11:44',
    'legs' => []
    ]
    */

    public function run()
    {
        // Получаем отсортированный по дате-времени вылета массив рейсов
        $this->sortedFlights = self::sortFlights(self::TEST_FLIGHTS);

        // Строим массив маршрутов
        $this->buildRoutes();

        // Получаем массив маршрутов, отсортированный по длительности маршрута
        $sortedRoutes = array_combine(array_column($this->routes, 'duration'), $this->routes);
        krsort($sortedRoutes);

        // Отдаем ИНФО о самом длительном маршруте
        return $sortedRoutes[array_key_first($sortedRoutes)];
    }

    /**
     * Рассчитывает длительность рейса В СЕКУНДАХ с учетом времени пересадки на него (если задано)
     * @param array $flight
     * @param string $routeArrival
     * @return int
     */
    private static function calcFlightDuration(array $flight, string $routeArrival = ''): int
    {
        return date('U', strtotime($flight['arrival'])) - date('U', strtotime($routeArrival ?: $flight['depart']));
    }

    /**
     * Возвращает массив рейсов, отсортированный по времени вылета
     * @param array $flights
     * @return array
     */
    private static function sortFlights(array $flights): array
    {
        usort(
            $flights,
            function ($flight1, $flight2) {
                return date('U', strtotime($flight1['depart'])) - date('U', strtotime($flight2['depart']));
            }
        );

        return $flights;
    }

    /**
     * Строит массив маршрутов
     */
    private function buildRoutes(): void
    {
        // Алгоритм поиска возможных маршрутов
        // Каждый рейс проверяется на возможность подключения к имеющимся маршрутам
        // и в случае такой возможности создается новый маршрут
        foreach ($this->sortedFlights as $flight) {
            foreach ($this->routes as $route) {
                if ($flight['from'] == $route['to'] &&
                    date('U', strtotime($flight['depart'])) > date('U', strtotime($route['arrival']))
                ) {
                    $this->appendRoute($flight, $route);
                }
            }

            // Каждый рейс в отдельности создает новый маршрут
            $this->appendRoute($flight);
        }
    }

    /**
     * @param array $flight
     * @param array $route
     */
    private function appendRoute(array $flight, array $route = []): void
    {
        // Добавляем новую запись в маршруты с учетом предыдущей части маршрута (если задано)
        $this->routes[] = [
            'duration' => self::calcFlightDuration($flight, $route['arrival'] ?? '') + $route['duration'] ?? 0,
            'from' => $route['from'] ?? $flight['from'],
            'to' => $flight['to'],
            'depart' => $route['depart'] ?? $flight['depart'],
            'arrival' => $flight['arrival'],
            'legs' => array_merge($route['legs'] ?? [], [$flight]),
        ];
    }
}

print_r((new TestFlights())->run());