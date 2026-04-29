<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // --- INDIVIDUAL TESTS: BLOOD CHEMISTRY ---
            ['name' => 'FBS / RBS', 'price' => 140, 'cat' => 'individual', 'gender' => 'both', 'prep' => '8-10 hours fasting for FBS; No fasting for RBS.', 'desc' => 'Measures the amount of glucose (sugar) in your blood to screen for diabetes.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'BUN', 'price' => 150, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation required.', 'desc' => 'Blood Urea Nitrogen test to evaluate kidney function and waste filtration.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Creatinine', 'price' => 150, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation required.', 'desc' => 'Measures creatinine levels to monitor how well the kidneys are filtering waste.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'BUA (Uric Acid)', 'price' => 150, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation required.', 'desc' => 'Checks for high levels of uric acid, often used to diagnose gout or kidney stones.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Cholesterol', 'price' => 180, 'cat' => 'individual', 'gender' => 'both', 'prep' => '10-12 hours fasting required.', 'desc' => 'Total cholesterol test to assess the risk of cardiovascular diseases.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Triglycerides', 'price' => 200, 'cat' => 'individual', 'gender' => 'both', 'prep' => '10-12 hours fasting required.', 'desc' => 'Measures the amount of triglycerides (fat) in the blood.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'HDL', 'price' => 250, 'cat' => 'individual', 'gender' => 'both', 'prep' => '10-12 hours fasting required.', 'desc' => 'Measures "Good Cholesterol" which helps remove fat from your arteries.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'SGPT / ALT', 'price' => 210, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'Avoid heavy exercise before test.', 'desc' => 'Liver enzyme test to detect liver injury or hepatitis.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'SGOT / AST', 'price' => 210, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation required.', 'desc' => 'Evaluates liver health and identifies potential muscle or heart damage.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'HbA1C', 'price' => 800, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No fasting required.', 'desc' => 'Measures your average blood sugar levels over the past 3 months.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'OGCT', 'price' => 450, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'Drink glucose load as directed by lab.', 'desc' => 'Oral Glucose Challenge Test, commonly used for gestational diabetes screening.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'OGTT', 'price' => 750, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'Fasting required; multiple blood draws.', 'desc' => 'Oral Glucose Tolerance Test to diagnose diabetes or insulin resistance.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Lipid Profile Individual', 'price' => 600, 'cat' => 'individual', 'gender' => 'both', 'prep' => '10-12 hours fasting required.', 'desc' => 'Comprehensive check including Total Cholesterol, HDL, LDL, and Triglycerides.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Potassium', 'price' => 350, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation required.', 'desc' => 'Electrolyte test to monitor heart rhythm and muscle function.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Sodium', 'price' => 350, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation required.', 'desc' => 'Electrolyte test to monitor fluid balance and nerve function.', 'sample' => 'Blood', 'time' => 5],

            // --- INDIVIDUAL TESTS: HEPATITIS & IMMUNOLOGY ---
            ['name' => 'HBSAG (Qualitative)', 'price' => 150, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Rapid screening for Hepatitis B surface antigens.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Anti-HAV IGM/IGG', 'price' => 450, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Detects antibodies for Hepatitis A (past or present infection).', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'VDRL', 'price' => 250, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Venereal Disease Research Laboratory test, used to screen for syphilis.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'FT3', 'price' => 550, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Measures Free Triiodothyronine to evaluate thyroid activity.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'FT4', 'price' => 550, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Measures Free Thyroxine to diagnose hyper/hypothyroidism.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'TSH', 'price' => 550, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Thyroid Stimulating Hormone test to regulate thyroid gland function.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Thyroid Panel (FT3, FT4, TSH)', 'price' => 1500, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No fasting required.', 'desc' => 'Complete profile to assess overall thyroid health.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Dengue Duo', 'price' => 800, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Rapid test for Dengue NS1 antigen and IgG/IgM antibodies.', 'sample' => 'Blood', 'time' => 5],

            // --- INDIVIDUAL TESTS: HEMATOLOGY & MICROSCOPY ---
            ['name' => 'CBC with Platelet', 'price' => 150, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Complete Blood Count to check for anemia, infection, and clotting levels.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Platelet Count', 'price' => 90, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Stand-alone test to measure blood clotting cells.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'ABO / RH Typing', 'price' => 110, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'No special preparation.', 'desc' => 'Determines your blood group (A, B, AB, O) and Rh factor.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'Urinalysis', 'price' => 45, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'Mid-stream clean catch urine sample.', 'desc' => 'Microscopic and chemical analysis of urine to detect UTI or kidney issues.', 'sample' => 'Urine', 'time' => 5],
            ['name' => 'Fecalysis', 'price' => 50, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'Fresh stool sample in clean container.', 'desc' => 'Stool examination to check for parasites, bleeding, or digestive issues.', 'sample' => 'Stool', 'time' => 5],
            ['name' => 'Pregnancy Test (HCG)', 'price' => 150, 'cat' => 'individual', 'gender' => 'female', 'prep' => 'First morning urine preferred.', 'desc' => 'Rapid urine test to detect the HCG hormone.', 'sample' => 'Urine', 'time' => 5],

            // --- OTHER SERVICES ---
            ['name' => 'C-XRAY', 'price' => 220, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'Remove jewelry and metallic objects from chest area.', 'desc' => 'Chest X-ray to examine the heart, lungs, and surrounding bones.', 'sample' => 'N/A', 'time' => 15],
            ['name' => 'Drug Test', 'price' => 290, 'cat' => 'individual', 'gender' => 'both', 'prep' => 'Valid ID required; witnessed urine collection.', 'desc' => 'Standard screening for Methamphetamine and THC.', 'sample' => 'Urine', 'time' => 10],

            // --- SPECIAL PACKAGES ---
            ['name' => 'CHEM 5', 'price' => 450, 'cat' => 'package', 'gender' => 'both', 'prep' => '10 hours fasting.', 'desc' => 'Includes: FBS, BUA (Uric Acid), Creatinine, BUN.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'CHEM 8', 'price' => 680, 'cat' => 'package', 'gender' => 'both', 'prep' => '10-12 hours fasting.', 'desc' => 'Includes: FBS, BUN, SGPT, SGOT, Creatinine, BUA (Uric Acid), Cholesterol, Triglycerides.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'CHEM 10', 'price' => 880, 'cat' => 'package', 'gender' => 'both', 'prep' => '10-12 hours fasting.', 'desc' => 'Includes: FBS, BUN, SGPT, SGOT, Creatinine, BUA (Uric Acid), Lipid Profile (HDL, LDL, Chol, Trig).', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'CHEM 12', 'price' => 1700, 'cat' => 'package', 'gender' => 'both', 'prep' => '10-12 hours fasting.', 'desc' => 'Includes: FBS, BUN, SGPT, SGOT, Sodium, Potassium, Creatinine, BUA (Uric Acid).', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'LIPID PROFILE BUNDLE', 'price' => 600, 'cat' => 'package', 'gender' => 'both', 'prep' => '10-12 hours fasting.', 'desc' => 'Includes: HDL, LDL, Cholesterol, and Triglycerides.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'THYROID BUNDLE', 'price' => 1500, 'cat' => 'package', 'gender' => 'both', 'prep' => '10 hours fasting.', 'desc' => 'Includes: FBS, Uric Acid, Creatinine, and BUN.', 'sample' => 'Blood', 'time' => 5],
            ['name' => 'PEDIA PACKAGE', 'price' => 150, 'cat' => 'package', 'gender' => 'both', 'prep' => 'None.', 'desc' => 'Essential pediatric screening including CBC and Urinalysis.', 'sample' => 'Blood, Urine', 'time' => 10],

            // --- PREGNANCY PACKAGES ---
            ['name' => 'PREGNANCY PACKAGE A', 'price' => 380, 'cat' => 'package', 'gender' => 'female', 'prep' => 'Fast for 8 hours.', 'desc' => 'Includes: FBS, CBC, VDRL, HBSAG, Urinalysis, Blood Typing.', 'sample' => 'Blood, Urine', 'time' => 10],
            ['name' => 'PREGNANCY PACKAGE B', 'price' => 320, 'cat' => 'package', 'gender' => 'female', 'prep' => 'No fasting.', 'desc' => 'Includes: CBC, Blood Typing, HBSAG, Urinalysis.', 'sample' => 'Blood, Urine', 'time' => 10],
            ['name' => 'PREGNANCY PACKAGE C', 'price' => 700, 'cat' => 'package', 'gender' => 'female', 'prep' => 'Clinic-directed glucose prep.', 'desc' => 'Includes: CBC, Blood Typing, HBSAG, Urinalysis, OGCT.', 'sample' => 'Blood, Urine', 'time' => 10],
        ];

        foreach ($services as $s) {
            Service::updateOrCreate(
                ['name' => $s['name']],
                [
                    'price' => $s['price'],
                    'category' => $s['cat'],
                    'gender_restriction' => $s['gender'],
                    'description' => $s['desc'],
                    'preparation' => $s['prep'],
                    'sample_required' => $s['sample'], 
                    'estimated_time' => $s['time'],  
                    'is_available' => true
                ]
            );
        }
    }
}