<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Vendor;
use App\Models\City;
use App\Models\Area;
use App\Models\Province;
use Illuminate\Support\Facades\DB;

class LocationService
{
    /**
     * Pakistan Location Hierarchy: Province => [ City => [ Areas... ] ]
     */
    protected static array $defaultHierarchy = [
        'Sindh' => [
            'Karachi' => [
                'Clifton', 'DHA Defence', 'Saddar', 'Gulshan-e-Iqbal', 'Gulistan-e-Johar', 
                'North Nazimabad', 'Nazimabad', 'Korangi', 'Landhi', 'SITE Area', 'Malir', 
                'Federal B Area', 'PECHS', 'Tariq Road', 'Bahadurabad', 'Liaquatabad', 
                'Orangi Town', 'Lyari', 'Kemari', 'Shah Faisal Colony', 'Baldia Town', 
                'Scheme 33', 'New Karachi', 'Surjani Town', 'Boat Basin', 'Kharadar', 
                'Mithadar', 'Bolton Market', 'Jodia Bazar', 'Saddar Bazar', 'Gulshan-e-Maymar',
                'North Karachi', 'Garden East', 'Garden West', 'Buffer Zone'
            ],
            'Hyderabad' => [
                'Latifabad (Unit 1-12)', 'Qasimabad', 'Saddar', 'City / Resham Gali', 
                'Auto Bhan Road', 'Kohsar', 'Citizen Colony', 'Heerabad', 'Phuleli', 
                'Paretabad', 'Station Road', 'Wadhu Wah Road', 'Thandi Sarak', 'Gari Khata',
                'Hala Naka', 'Kotri', 'SITE Hyderabad'
            ],
            'Sukkur' => ['Military Road', 'Minara Road', 'Workshop Road', 'Barrage Colony', 'New Pind', 'Shikarpur Road', 'Clock Tower'],
            'Larkana' => ['VIP Road', 'Station Road', 'Bank Square', 'Lahori Mohalla', 'Bakrani Road'],
            'Mirpurkhas' => ['Satellite Town', 'Station Road', 'Ring Road', 'Khipro Road', 'Hirabad'],
            'Nawabshah' => ['Sakrand Road', 'Society', 'Civil Lines', 'Kazi Ahmed Road'],
            'Thatta' => ['Makli', 'City Center', 'Main Road'],
            'Badin' => ['Model Town', 'Station Road', 'Kadhan Road'],
            'Jacobabad' => ['Station Road', 'DC Office Road', 'Family Lines'],
            'Shikarpur' => ['Hathi Gate', 'Station Road', 'Lakhidar'],
            'Dadu' => ['Station Road', 'Makhdoom Bilawal Town', 'Cinema Road'],
            'Tando Allahyar' => ['Station Road', 'City Area', 'Mirwah Road'],
            'Tando Muhammad Khan' => ['Station Road', 'City Area'],
            'Khairpur' => ['Luqman', 'Mall Road', 'Station Road'],
            'Jamshoro' => ['Kotri', 'Mehran University Area', 'Sindh University Area'],
        ],
        'Punjab' => [
            'Lahore' => [
                'Gulberg (I-III)', 'DHA Defence (Phase 1-9)', 'Johar Town', 'Model Town', 
                'Lahore Cantt', 'Faisal Town', 'Allama Iqbal Town', 'Garden Town', 'Shadman', 
                'Anarkali', 'Mall Road', 'Shah Alam Market', 'Badami Bagh', 'Township', 
                'Wapda Town', 'Bahria Town', 'Sabzazar', 'Samanabad', 'Mughalpura', 
                'Brandreth Road', 'Hall Road', 'Urdu Bazar', 'Azam Cloth Market', 
                'Cavalry Ground', 'Walton', 'Barki Road', 'Ferozepur Road', 'Gulshan-e-Ravi',
                'Misri Shah', 'Daroghawala'
            ],
            'Faisalabad' => [
                'D Ground', 'Peoples Colony', 'Madina Town', 'Ghulam Muhammad Abad', 
                'Kohinoor City', 'Clock Tower (8 Bazars)', 'Jaranwala Road', 'Satiana Road', 
                'Susan Road', 'Jail Road', 'Samanabad', 'Canal Road', 'Millat Town'
            ],
            'Rawalpindi' => [
                'Saddar', 'Satellite Town', 'Bahria Town', 'Chaklala Scheme (I-III)', 
                'Westridge', 'Raja Bazar', 'Commercial Market', 'Peshawar Road', 
                'Murree Road', 'Gulraiz Housing', 'Adyala Road'
            ],
            'Multan' => [
                'Multan Cantt', 'Gulgasht Colony', 'Bosan Road', 'Shah Rukn-e-Alam Colony', 
                'New Multan', 'Delhi Gate', 'Hussain Agahi', 'Abdali Road', 'Suraj Miani'
            ],
            'Gujranwala' => [
                'Model Town', 'Satellite Town', 'Gujranwala Cantt', 'Civil Lines', 
                'GT Road', 'DC Colony', 'Peoples Colony', 'Wapda Town', 'Rahwali'
            ],
            'Sialkot' => [
                'Sialkot Cantt', 'Model Town', 'Paris Road', 'Small Industrial Estate', 
                'Rangpura', 'Kashmir Road', 'Defence Road'
            ],
            'Sargodha' => ['Satellite Town', 'University Road', 'Fatima Jinnah Colony', 'Club Road', 'Cantt'],
            'Bahawalpur' => ['Model Town (A-C)', 'Cantt', 'Circular Road', 'One Unit Staff Colony'],
            'Sheikhupura' => ['Housing Colony', 'Civil Lines', 'Lahore Road', 'Sargodha Road'],
            'Rahim Yar Khan' => ['Model Town', 'Abbasia Town', 'Shahi Road', 'Gulshan-e-Iqbal'],
            'Jhang' => ['Civil Lines', 'Saddar', 'Gojra Road', 'Ayub Chowk'],
            'Gujrat' => ['Court Road', 'Circular Road', 'Rehman Shaheed Road', 'G.T Road'],
            'Sahiwal' => ['Farid Town', 'Scheme No. 2', 'High Street', 'Civil Lines'],
            'Okara' => ['Faisal Colony', 'Mandir Road', 'Depalpur Road', 'G.T Road'],
            'Kasur' => ['Railway Road', 'Bhimke Road', 'Shahbaz Khan Road'],
            'Dera Ghazi Khan' => ['Block 1-20', 'College Road', 'Jampur Road'],
        ],
        'Khyber Pakhtunkhwa' => [
            'Peshawar' => [
                'Hayatabad (Phase 1-7)', 'University Town', 'Peshawar Cantt', 'Saddar', 
                'Qissa Khwani Bazar', 'Warsak Road', 'Kohat Road', 'Gulbahar', 
                'Ring Road', 'Dalazak Road', 'Charsadda Road'
            ],
            'Abbottabad' => ['Mandian', 'Supply', 'Abbottabad Cantt', 'Jinnahabad', 'PMA Kakul Road', 'Mansehra Road'],
            'Mardan' => ['Mardan Cantt', 'Baghdada', 'Shamsi Road', 'Bank Road', 'Gujar Garhi'],
            'Swat' => ['Mingora', 'Saidu Sharif', 'Gulkada', 'Fizagat'],
            'Kohat' => ['Kohat Cantt', 'KDA Kohat', 'Bannu Road', 'Rawalpindi Road'],
            'Dera Ismail Khan' => ['Circular Road', 'Cantt', 'Topanwala Gate', 'Bannu Road'],
            'Haripur' => ['Model Town', 'Circular Road', 'G.T Road', 'TIP Colony'],
            'Nowshera' => ['Nowshera Cantt', 'G.T Road', 'Risalpur', 'Cavagnari Road'],
            'Mansehra' => ['Karakoram Highway', 'College Road', 'Shinkiari Road'],
        ],
        'Balochistan' => [
            'Quetta' => [
                'Jinnah Road', 'Quetta Cantt', 'Satellite Town', 'Zarghoon Road', 
                'Samungli Road', 'Liaquat Bazar', 'Sariab Road', 'Brewery Road', 'Chaman Phatak'
            ],
            'Gwadar' => ['Port Road', 'Marine Drive', 'New Town', 'Airport Road', 'Syed Hashmi Avenue'],
            'Turbat' => ['Main Bazar', 'Airport Road', 'Absar'],
            'Hub' => ['Industrial Area', 'Main RCD Highway', 'Jam Colony', 'Allahabad'],
            'Khuzdar' => ['Cantt', 'RCD Highway', 'Sultan Ibrahim Road'],
            'Chaman' => ['Taj Road', 'Boghara Road', 'Mall Road'],
        ],
        'Islamabad Capital Territory' => [
            'Islamabad' => [
                'Blue Area', 'Sector F-6', 'Sector F-7', 'Sector F-8', 'Sector F-10', 'Sector F-11',
                'Sector G-6', 'Sector G-7', 'Sector G-8', 'Sector G-9', 'Sector G-10', 'Sector G-11',
                'Sector I-8', 'Sector I-9', 'Sector I-10', 'DHA Islamabad', 'Bahria Town Islamabad', 
                'Sector E-11', 'Sector H-8', 'Sector H-9', 'PWD Housing Society', 'Gulberg Greens', 
                'Ghouri Town', 'Bani Gala', 'Tarlai', 'Rawat'
            ],
        ],
        'Azad Jammu & Kashmir' => [
            'Muzaffarabad' => ['CMH Road', 'Domel', 'Plate', 'Gojra', 'Upper Chattar'],
            'Mirpur' => ['Sector F-1 to F-4', 'Allama Iqbal Road', 'Kalyal', 'Chowk Shaheedan'],
            'Rawalakot' => ['Main Bazar', 'Housing Scheme', 'Singola Road'],
            'Kotli' => ['Shaheed Chowk', 'Gulhar', 'Pandi Road'],
        ],
        'Gilgit-Baltistan' => [
            'Gilgit' => ['Jutial', 'Cantt', 'Nagaral', 'Kashrote', 'Airport Road'],
            'Skardu' => ['Main Bazar', 'Haji Gam', 'Aliabad', 'College Road'],
            'Hunza' => ['Karimabad', 'Aliabad', 'Ganish'],
        ],
    ];

    /**
     * Get Complete Hierarchy (Dynamic from Database stored active provinces)
     */
    public function getHierarchy(): array
    {
        $hierarchy = [];

        // 1. Initialize strictly from created active provinces in database
        $createdProvinces = $this->getProvinces();
        foreach ($createdProvinces as $pName) {
            $hierarchy[$pName] = [];
        }

        // Helper function for matching province name case-insensitively against created provinces
        $findMatchingProvince = function ($p) use ($hierarchy) {
            $trimmed = trim($p ?? '');
            if (empty($trimmed)) return null;
            foreach (array_keys($hierarchy) as $provKey) {
                if (strcasecmp($provKey, $trimmed) === 0) {
                    return $provKey;
                }
            }
            return null;
        };

        // 2. Fetch from cities table (only attach to matching created provinces)
        try {
            $dbCities = City::all();
            foreach ($dbCities as $c) {
                $p = $findMatchingProvince($c->province ?? '');
                $cName = trim($c->name ?? '');
                if (empty($cName) || empty($p)) continue;
                if (!isset($hierarchy[$p][$cName])) {
                    $hierarchy[$p][$cName] = [];
                }
            }
        } catch (\Throwable $e) {}

        // 3. Fetch from areas table
        try {
            $dbAreas = Area::all();
            foreach ($dbAreas as $a) {
                $p = $findMatchingProvince($a->province ?? '');
                $cName = trim($a->city_name ?? '');
                $aName = trim($a->name ?? '');
                if (empty($cName) || empty($aName)) continue;

                if ($p && isset($hierarchy[$p][$cName])) {
                    if (!in_array($aName, $hierarchy[$p][$cName])) {
                        $hierarchy[$p][$cName][] = $aName;
                    }
                } else {
                    foreach ($hierarchy as $provKey => $cities) {
                        if (isset($cities[$cName])) {
                            if (!in_array($aName, $hierarchy[$provKey][$cName])) {
                                $hierarchy[$provKey][$cName][] = $aName;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 4. Fetch distinct customer & vendor locations from DB (only for matching created provinces)
        try {
            $custLocs = DB::table('customers')
                ->select('province', 'city', 'area')
                ->whereNotNull('city')
                ->where('city', '<>', '')
                ->distinct()
                ->get();

            $vendLocs = DB::table('vendors')
                ->select('province', 'city', 'area')
                ->whereNotNull('city')
                ->where('city', '<>', '')
                ->distinct()
                ->get();

            $allLocs = $custLocs->concat($vendLocs);

            foreach ($allLocs as $loc) {
                $p = $findMatchingProvince($loc->province ?? '');
                $c = trim($loc->city ?? '');
                $a = trim($loc->area ?? '');

                if (empty($c) || empty($p)) continue;

                if (!isset($hierarchy[$p][$c])) {
                    $hierarchy[$p][$c] = [];
                }
                if (!empty($a) && !in_array($a, $hierarchy[$p][$c])) {
                    $hierarchy[$p][$c][] = $a;
                }
            }
        } catch (\Throwable $e) {}

        return $hierarchy;
    }

    /**
     * Get List of all Provinces (Only created in database, dynamic)
     */
    public function getProvinces(): array
    {
        try {
            return Province::active()->orderBy('name')->pluck('name')->map(fn($n) => trim($n))->unique()->values()->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get List of Cities (Optionally filtered by Province)
     */
    public function getCities(?string $province = null): array
    {
        $hierarchy = $this->getHierarchy();

        if (!empty($province) && $province !== 'all') {
            if (isset($hierarchy[$province])) {
                return array_keys($hierarchy[$province]);
            }
            // Case-insensitive match fallback
            foreach ($hierarchy as $pName => $cities) {
                if (strcasecmp($pName, $province) === 0) {
                    return array_keys($cities);
                }
            }
            return [];
        }

        // Return all unique cities
        $allCities = [];
        foreach ($hierarchy as $pName => $cities) {
            foreach (array_keys($cities) as $cName) {
                $allCities[$cName] = true;
            }
        }
        ksort($allCities);
        return array_keys($allCities);
    }

    /**
     * Get List of Areas (Optionally filtered by City)
     */
    public function getAreas(?string $city = null): array
    {
        $hierarchy = $this->getHierarchy();

        if (!empty($city) && $city !== 'all') {
            foreach ($hierarchy as $pName => $cities) {
                foreach ($cities as $cName => $areas) {
                    if (strcasecmp($cName, $city) === 0) {
                        return $areas;
                    }
                }
            }
            return [];
        }

        // Return all unique areas
        $allAreas = [];
        foreach ($hierarchy as $pName => $cities) {
            foreach ($cities as $cName => $areas) {
                foreach ($areas as $aName) {
                    $allAreas[$aName] = true;
                }
            }
        }
        ksort($allAreas);
        return array_keys($allAreas);
    }

    /**
     * Get Customers filtered by Province, City, Area
     */
    public function getFilteredCustomers(?string $province = null, ?string $city = null, ?string $area = null)
    {
        $query = Customer::query()->select('id', 'customer_name', 'customer_id', 'province', 'city', 'area');

        if (!empty($province) && $province !== 'all') {
            $query->where('province', $province);
        }
        if (!empty($city) && $city !== 'all') {
            $query->where('city', $city);
        }
        if (!empty($area) && $area !== 'all') {
            $query->where('area', $area);
        }

        return $query->orderBy('customer_name')->get();
    }

    /**
     * Get Vendors filtered by Province, City, Area, Zone
     */
    public function getFilteredVendors(?string $province = null, ?string $city = null, ?string $area = null, ?string $zone = null)
    {
        $query = Vendor::query()->select('id', 'name', 'province', 'city', 'area', 'zone');

        if (!empty($province) && $province !== 'all') {
            $query->where('province', $province);
        }
        if (!empty($city) && $city !== 'all') {
            $query->where('city', $city);
        }
        if (!empty($area) && $area !== 'all') {
            $query->where('area', $area);
        }
        if (!empty($zone) && $zone !== 'all') {
            $query->where('zone', $zone);
        }

        return $query->orderBy('name')->get();
    }
}
