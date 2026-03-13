<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WeatherTimeController extends Controller
{
    /**
     * Obtenir l'heure et la météo pour le Cameroun
     */
    public function getCameroonWeatherTime()
    {
        try {
            // Villes principales du Cameroun
            $cities = [
                'Yaoundé' => ['lat' => 3.8480, 'lon' => 11.5021],
                'Douala' => ['lat' => 4.0511, 'lon' => 9.7679],
                'Bafoussam' => ['lat' => 5.4781, 'lon' => 10.4199],
                'Bamenda' => ['lat' => 5.9631, 'lon' => 10.1591],
                'Garoua' => ['lat' => 9.3265, 'lon' => 13.3958]
            ];

            $weatherData = [];

            foreach ($cities as $city => $coords) {
                // Cache pour 10 minutes
                $cacheKey = "weather_time_{$city}";
                
                $data = Cache::remember($cacheKey, 600, function () use ($city, $coords) {
                    return $this->fetchWeatherData($city, $coords);
                });

                $weatherData[$city] = $data;
            }

            // Heure actuelle du Cameroun (UTC+1)
            $cameroonTime = now()->setTimezone('Africa/Douala');

            return response()->json([
                'success' => true,
                'data' => [
                    'current_time' => [
                        'datetime' => $cameroonTime->format('Y-m-d H:i:s'),
                        'date' => $cameroonTime->format('d/m/Y'),
                        'time' => $cameroonTime->format('H:i'),
                        'day' => $cameroonTime->format('l'),
                        'day_fr' => $this->getDayInFrench($cameroonTime->format('l')),
                        'timezone' => 'Africa/Douala (UTC+1)'
                    ],
                    'weather' => $weatherData,
                    'country_info' => [
                        'name' => 'République du Cameroun',
                        'capital' => 'Yaoundé',
                        'motto' => 'Paix - Travail - Patrie',
                        'languages' => ['Français', 'Anglais'],
                        'currency' => 'Franc CFA (XAF)',
                        'flag' => '🇨🇲'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage(),
                'data' => $this->getFallbackData()
            ]);
        }
    }

    /**
     * Récupérer les données météo pour une ville
     */
    private function fetchWeatherData($city, $coords)
    {
        try {
            // Utilisation d'OpenWeatherMap API (gratuite)
            $apiKey = env('OPENWEATHER_API_KEY', 'demo_key');
            
            if ($apiKey === 'demo_key') {
                return $this->getMockWeatherData($city);
            }

            $response = Http::timeout(5)->get("https://api.openweathermap.org/data/2.5/weather", [
                'lat' => $coords['lat'],
                'lon' => $coords['lon'],
                'appid' => $apiKey,
                'units' => 'metric',
                'lang' => 'fr'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'temperature' => round($data['main']['temp']),
                    'description' => ucfirst($data['weather'][0]['description']),
                    'humidity' => $data['main']['humidity'],
                    'pressure' => $data['main']['pressure'],
                    'wind_speed' => $data['wind']['speed'] ?? 0,
                    'icon' => $data['weather'][0]['icon'],
                    'feels_like' => round($data['main']['feels_like'])
                ];
            }

            return $this->getMockWeatherData($city);

        } catch (\Exception $e) {
            return $this->getMockWeatherData($city);
        }
    }

    /**
     * Données météo simulées pour le Cameroun
     */
    private function getMockWeatherData($city)
    {
        $mockData = [
            'Yaoundé' => ['temp' => 26, 'desc' => 'Partiellement nuageux', 'humidity' => 75],
            'Douala' => ['temp' => 28, 'desc' => 'Ensoleillé', 'humidity' => 80],
            'Bafoussam' => ['temp' => 22, 'desc' => 'Nuageux', 'humidity' => 70],
            'Bamenda' => ['temp' => 20, 'desc' => 'Brouillard matinal', 'humidity' => 85],
            'Garoua' => ['temp' => 32, 'desc' => 'Très ensoleillé', 'humidity' => 45]
        ];

        $data = $mockData[$city] ?? $mockData['Yaoundé'];

        return [
            'temperature' => $data['temp'],
            'description' => $data['desc'],
            'humidity' => $data['humidity'],
            'pressure' => rand(1010, 1020),
            'wind_speed' => rand(5, 15),
            'icon' => '01d',
            'feels_like' => $data['temp'] + rand(-2, 3)
        ];
    }

    /**
     * Traduction des jours en français
     */
    private function getDayInFrench($day)
    {
        $days = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche'
        ];

        return $days[$day] ?? $day;
    }

    /**
     * Données de secours en cas d'erreur
     */
    private function getFallbackData()
    {
        $cameroonTime = now()->setTimezone('Africa/Douala');

        return [
            'current_time' => [
                'datetime' => $cameroonTime->format('Y-m-d H:i:s'),
                'date' => $cameroonTime->format('d/m/Y'),
                'time' => $cameroonTime->format('H:i'),
                'day' => $cameroonTime->format('l'),
                'day_fr' => $this->getDayInFrench($cameroonTime->format('l')),
                'timezone' => 'Africa/Douala (UTC+1)'
            ],
            'weather' => [
                'Yaoundé' => $this->getMockWeatherData('Yaoundé'),
                'Douala' => $this->getMockWeatherData('Douala')
            ],
            'country_info' => [
                'name' => 'République du Cameroun',
                'capital' => 'Yaoundé',
                'motto' => 'Paix - Travail - Patrie',
                'languages' => ['Français', 'Anglais'],
                'currency' => 'Franc CFA (XAF)',
                'flag' => '🇨🇲'
            ]
        ];
    }
}