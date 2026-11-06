<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // =========================================================================
        // 1. SEED UNIQUE ATOMIC LOOKUP SAMPLES
        // =========================================================================
        $sampleNames = ['Blood', 'Urine', 'Stool', 'N/A'];
        
        foreach ($sampleNames as $name) {
            DB::table('samples')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // =========================================================================
        // 2. ATOMIC INDIVIDUAL CLINICAL SERVICES (Strictly non-bundle tests)
        // =========================================================================
        $individualServices = [
            // --- BLOOD CHEMISTRY ---
            [
                'name' => 'FBS / RBS',
                'price' => 140,
                'gender' => 'both',
                'prep' => '8-10 hours fasting for FBS; No fasting for RBS.',
                'desc' => 'Measures the amount of glucose (sugar) in your blood to screen for diabetes.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'BUN',
                'price' => 150,
                'gender' => 'both',
                'prep' => 'No special preparation required.',
                'desc' => 'Blood Urea Nitrogen test to evaluate kidney function and waste filtration.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Creatinine',
                'price' => 150,
                'gender' => 'both',
                'prep' => 'No special preparation required.',
                'desc' => 'Measures creatinine levels to monitor how well the kidneys are filtering waste.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'BUA (Uric Acid)',
                'price' => 150,
                'gender' => 'both',
                'prep' => 'No special preparation required.',
                'desc' => 'Checks for high levels of uric acid, often used to diagnose gout or kidney stones.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Cholesterol',
                'price' => 180,
                'gender' => 'both',
                'prep' => '10-12 hours fasting required.',
                'desc' => 'Total cholesterol test to assess the risk of cardiovascular diseases.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Triglycerides',
                'price' => 200,
                'gender' => 'both',
                'prep' => '10-12 hours fasting required.',
                'desc' => 'Measures the amount of triglycerides (fat) in the blood.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'HDL',
                'price' => 250,
                'gender' => 'both',
                'prep' => '10-12 hours fasting required.',
                'desc' => 'Measures "Good Cholesterol" which helps remove fat from your arteries.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'LDL',
                'price' => 250,
                'gender' => 'both',
                'prep' => '10-12 hours fasting required.',
                'desc' => 'Measures "Bad Cholesterol" to assess cardiovascular health.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'SGPT / ALT',
                'price' => 210,
                'gender' => 'both',
                'prep' => 'Avoid heavy exercise before test.',
                'desc' => 'Liver enzyme test to detect liver injury or hepatitis.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'SGOT / AST',
                'price' => 210,
                'gender' => 'both',
                'prep' => 'No special preparation required.',
                'desc' => 'Evaluates liver health and identifies potential muscle or heart damage.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'HbA1C',
                'price' => 800,
                'gender' => 'both',
                'prep' => 'No fasting required.',
                'desc' => 'Measures your average blood sugar levels over the past 3 months.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'OGCT',
                'price' => 450,
                'gender' => 'both',
                'prep' => 'Drink glucose load as directed by lab.',
                'desc' => 'Oral Glucose Challenge Test, commonly used for gestational diabetes screening.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'OGTT',
                'price' => 750,
                'gender' => 'both',
                'prep' => 'Fasting required; multiple blood draws.',
                'desc' => 'Oral Glucose Tolerance Test to diagnose diabetes or insulin resistance.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Potassium',
                'price' => 350,
                'gender' => 'both',
                'prep' => 'No special preparation required.',
                'desc' => 'Electrolyte test to monitor heart rhythm and muscle function.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Sodium',
                'price' => 350,
                'gender' => 'both',
                'prep' => 'No special preparation required.',
                'desc' => 'Electrolyte test to monitor fluid balance and nerve function.',
                'sample' => 'Blood',
                'time' => 5
            ],

            // --- HEPATITIS & IMMUNOLOGY ---
            [
                'name' => 'HBSAG (Qualitative)',
                'price' => 150,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Rapid screening for Hepatitis B surface antigens.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Anti-HAV IGM/IGG',
                'price' => 450,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Detects antibodies for Hepatitis A (past or present infection).',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'VDRL',
                'price' => 250,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Venereal Disease Research Laboratory test, used to screen for syphilis.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'FT3',
                'price' => 550,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Measures Free Triiodothyronine to evaluate thyroid activity.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'FT4',
                'price' => 550,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Measures Free Thyroxine to diagnose hyper/hypothyroidism.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'TSH',
                'price' => 550,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Thyroid Stimulating Hormone test to regulate thyroid gland function.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Dengue Duo',
                'price' => 800,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Rapid test for Dengue NS1 antigen and IgG/IgM antibodies.',
                'sample' => 'Blood',
                'time' => 5
            ],

            // --- HEMATOLOGY & MICROSCOPY ---
            [
                'name' => 'CBC with Platelet',
                'price' => 150,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Complete Blood Count to check for anemia, infection, and clotting levels.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Platelet Count',
                'price' => 90,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Stand-alone test to measure blood clotting cells.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'ABO / RH Typing',
                'price' => 110,
                'gender' => 'both',
                'prep' => 'No special preparation.',
                'desc' => 'Determines your blood group (A, B, AB, O) and Rh factor.',
                'sample' => 'Blood',
                'time' => 5
            ],
            [
                'name' => 'Urinalysis',
                'price' => 45,
                'gender' => 'both',
                'prep' => 'Mid-stream clean catch urine sample.',
                'desc' => 'Microscopic and chemical analysis of urine to detect UTI or kidney issues.',
                'sample' => 'Urine',
                'time' => 5
            ],
            [
                'name' => 'Fecalysis',
                'price' => 50,
                'gender' => 'both',
                'prep' => 'Fresh stool sample in clean container.',
                'desc' => 'Stool examination to check for parasites, bleeding, or digestive issues.',
                'sample' => 'Stool',
                'time' => 5
            ],
            [
                'name' => 'Pregnancy Test (HCG)',
                'price' => 150,
                'gender' => 'female',
                'prep' => 'First morning urine preferred.',
                'desc' => 'Rapid urine test to detect the HCG hormone.',
                'sample' => 'Urine',
                'time' => 5
            ],

            // --- RADIOLOGY & CLINICAL SERVICES ---
            [
                'name' => 'C-XRAY',
                'price' => 220,
                'gender' => 'both',
                'prep' => 'Remove jewelry and metallic objects from chest area.',
                'desc' => 'Chest X-ray to examine the heart, lungs, and surrounding bones.',
                'sample' => 'N/A',
                'time' => 15
            ],
            [
                'name' => 'Drug Test',
                'price' => 290,
                'gender' => 'both',
                'prep' => 'Valid ID required; witnessed urine collection.',
                'desc' => 'Standard screening for Methamphetamine and THC.',
                'sample' => 'Urine',
                'time' => 10
            ],
            [
                'name' => 'Medical Certificate',
                'price' => 130,
                'gender' => 'both',
                'prep' => 'None.',
                'desc' => 'Official document certifying medical fitness or illness.',
                'sample' => 'N/A',
                'time' => 5
            ],
            [
                'name' => 'ECG',
                'price' => 220,
                'gender' => 'both',
                'prep' => 'Avoid caffeine and heavy meals before test.',
                'desc' => 'Electrocardiogram to assess heart rhythm and electrical activity.',
                'sample' => 'N/A',
                'time' => 15
            ],
        ];

        // Seed individual services and sync biological specimens
        foreach ($individualServices as $s) {
            $service = Service::updateOrCreate(
                ['name' => $s['name']],
                [
                    'price' => $s['price'],
                    'gender_restriction' => $s['gender'],
                    'description' => $s['desc'],
                    'preparation' => $s['prep'],
                    'estimated_time' => $s['time'],
                    'is_available' => true
                ]
            );

            // Synchronize biological specimen pivot
            $samplesToAttach = array_map('trim', explode(',', $s['sample']));
            $sampleIds = [];
            foreach ($samplesToAttach as $sampleName) {
                $sampleId = DB::table('samples')->where('name', $sampleName)->value('id');
                if ($sampleId) {
                    $sampleIds[] = $sampleId;
                }
            }

            DB::table('service_sample')->where('service_id', $service->id)->delete();
            foreach ($sampleIds as $id) {
                DB::table('service_sample')->insert([
                    'service_id' => $service->id,
                    'sample_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // =========================================================================
        // 3. DEDICATED DIAGNOSTIC PACKAGES (Composed of atomic services)
        // =========================================================================
        $packages = [
            [
                'name' => 'CHEM 5',
                'price' => 450,
                'gender' => 'both',
                'prep' => '10 hours fasting.',
                'desc' => 'Includes: FBS, BUA (Uric Acid), Creatinine, BUN.',
                'time' => 10,
                'services' => ['FBS / RBS', 'BUA (Uric Acid)', 'Creatinine', 'BUN']
            ],
            [
                'name' => 'CHEM 8',
                'price' => 680,
                'gender' => 'both',
                'prep' => '10-12 hours fasting.',
                'desc' => 'Includes: FBS, BUN, SGPT, SGOT, Creatinine, BUA (Uric Acid), Cholesterol, Triglycerides.',
                'time' => 15,
                'services' => ['FBS / RBS', 'BUN', 'SGPT / ALT', 'SGOT / AST', 'Creatinine', 'BUA (Uric Acid)', 'Cholesterol', 'Triglycerides']
            ],
            [
                'name' => 'CHEM 10',
                'price' => 880,
                'gender' => 'both',
                'prep' => '10-12 hours fasting.',
                'desc' => 'Includes: FBS, BUN, SGPT, SGOT, Creatinine, BUA (Uric Acid), Lipid Profile (HDL, LDL, Chol, Trig).',
                'time' => 15,
                'services' => ['FBS / RBS', 'BUN', 'SGPT / ALT', 'SGOT / AST', 'Creatinine', 'BUA (Uric Acid)', 'Cholesterol', 'Triglycerides', 'HDL', 'LDL']
            ],
            [
                'name' => 'CHEM 12',
                'price' => 1700,
                'gender' => 'both',
                'prep' => '10-12 hours fasting.',
                'desc' => 'Includes: FBS, BUN, SGPT, SGOT, Sodium, Potassium, Creatinine, BUA (Uric Acid).',
                'time' => 20,
                'services' => ['FBS / RBS', 'BUN', 'SGPT / ALT', 'SGOT / AST', 'Sodium', 'Potassium', 'Creatinine', 'BUA (Uric Acid)']
            ],
            [
                'name' => 'LIPID PROFILE BUNDLE',
                'price' => 600,
                'gender' => 'both',
                'prep' => '10-12 hours fasting.',
                'desc' => 'Comprehensive check including Total Cholesterol, HDL, LDL, and Triglycerides.',
                'time' => 10,
                'services' => ['HDL', 'LDL', 'Cholesterol', 'Triglycerides']
            ],
            [
                'name' => 'THYROID BUNDLE',
                'price' => 1500,
                'gender' => 'both',
                'prep' => 'No fasting required.',
                'desc' => 'Comprehensive thyroid panel including FT3, FT4, and TSH.',
                'time' => 10,
                'services' => ['FT3', 'FT4', 'TSH']
            ],
            [
                'name' => 'PEDIA PACKAGE',
                'price' => 150,
                'gender' => 'both',
                'prep' => 'None.',
                'desc' => 'Essential pediatric screening including CBC and Urinalysis.',
                'time' => 10,
                'services' => ['CBC with Platelet', 'Urinalysis']
            ],
            [
                'name' => 'PREGNANCY PACKAGE A',
                'price' => 380,
                'gender' => 'female',
                'prep' => 'Fast for 8 hours.',
                'desc' => 'Includes: FBS, CBC, VDRL, HBSAG, Urinalysis, Blood Typing.',
                'time' => 15,
                'services' => ['FBS / RBS', 'CBC with Platelet', 'VDRL', 'HBSAG (Qualitative)', 'Urinalysis', 'ABO / RH Typing']
            ],
            [
                'name' => 'PREGNANCY PACKAGE B',
                'price' => 320,
                'gender' => 'female',
                'prep' => 'No fasting.',
                'desc' => 'Includes: CBC, Blood Typing, HBSAG, Urinalysis.',
                'time' => 15,
                'services' => ['CBC with Platelet', 'ABO / RH Typing', 'HBSAG (Qualitative)', 'Urinalysis']
            ],
            [
                'name' => 'PREGNANCY PACKAGE C',
                'price' => 700,
                'gender' => 'female',
                'prep' => 'Clinic-directed glucose prep.',
                'desc' => 'Includes: CBC, Blood Typing, HBSAG, Urinalysis, OGCT.',
                'time' => 20,
                'services' => ['CBC with Platelet', 'ABO / RH Typing', 'HBSAG (Qualitative)', 'Urinalysis', 'OGCT']
            ],
        ];

        // Seed packages and link constituent individual services into package_service pivot
        foreach ($packages as $pkgData) {
            $package = Package::updateOrCreate(
                ['name' => $pkgData['name']],
                [
                    'price' => $pkgData['price'],
                    'gender_restriction' => $pkgData['gender'],
                    'description' => $pkgData['desc'],
                    'preparation' => $pkgData['prep'],
                    'estimated_time' => $pkgData['time'],
                    'is_available' => true
                ]
            );

            // Resolve service IDs and synchronize
            $serviceIds = Service::whereIn('name', $pkgData['services'])->pluck('id')->toArray();
            $package->services()->sync($serviceIds);
        }
    }
}