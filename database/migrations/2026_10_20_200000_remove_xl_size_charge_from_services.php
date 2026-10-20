<?php

use App\Models\Service;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $services = Service::all();
        foreach ($services as $service) {
            $opts = is_string($service->options_config) ? json_decode($service->options_config, true) : $service->options_config;
            if (is_string($opts)) $opts = json_decode($opts, true);
            if (!is_array($opts)) $opts = [];

            if (isset($opts['size_pricing']) && is_array($opts['size_pricing'])) {
                // Size XL is free of surcharge
                $opts['size_pricing']['XL'] = 0;
                
                // XXL and larger have surcharge
                if (!isset($opts['size_pricing']['XXL']) || $opts['size_pricing']['XXL'] <= 0) {
                    $opts['size_pricing']['XXL'] = 10000;
                }

                $opts['size_pricing']['S'] = 0;
                $opts['size_pricing']['M'] = 0;
                $opts['size_pricing']['L'] = 0;

                $service->options_config = json_encode($opts);
                $service->save();
            }
        }
    }

    public function down(): void
    {
        $services = Service::all();
        foreach ($services as $service) {
            $opts = is_string($service->options_config) ? json_decode($service->options_config, true) : $service->options_config;
            if (is_string($opts)) $opts = json_decode($opts, true);
            if (!is_array($opts)) $opts = [];

            if (isset($opts['size_pricing']) && is_array($opts['size_pricing'])) {
                $opts['size_pricing']['XL'] = 5000;
                $service->options_config = json_encode($opts);
                $service->save();
            }
        }
    }
};
