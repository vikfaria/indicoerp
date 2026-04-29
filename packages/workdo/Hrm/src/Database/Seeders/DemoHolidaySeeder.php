<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\Holiday;
use Workdo\Hrm\Models\HolidayType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DemoHolidaySeeder extends Seeder
{
    public function run($userId): void
    {
        if (Holiday::where('created_by', $userId)->exists()) {
            return; // Skip seeding if data already exists
        }
        
        if (!empty($userId)) {
            $holidayTypes = HolidayType::where('created_by', $userId)->pluck('id')->toArray();

            if (empty($holidayTypes)) {
                return;
            }

            $holidayNames = [
                'New Year Day - Annual Celebration',
                'Martin Luther King Jr Day - Civil Rights',
                'Presidents Day - National Observance',
                'Good Friday - Religious Holiday',
                'Easter Monday - Religious Observance',
                'Memorial Day - National Remembrance',
                'Independence Day - National Holiday',
                'Labor Day - Workers Rights',
                'Columbus Day - Historical Observance',
                'Veterans Day - Military Appreciation',
                'Thanksgiving Day - National Gratitude',
                'Christmas Eve - Religious Preparation',
                'Christmas Day - Religious Celebration',
                'New Years Eve - Year End',
                'Company Foundation Day - Anniversary',
                'Employee Appreciation Day - Recognition',
                'Summer Company Retreat - Team Building',
                'Winter Holiday Break - Seasonal',
                'Spring Festival - Cultural Celebration',
                'Autumn Harvest Festival - Seasonal',
                'International Womens Day - Recognition',
                'Earth Day - Environmental Awareness',
                'World Health Day - Wellness Focus',
                'International Workers Day - Labor Rights',
                'World Environment Day - Sustainability',
                'International Peace Day - Global Unity',
                'World Teachers Day - Education Honor',
                'International Human Rights Day - Justice',
                'World AIDS Day - Health Awareness',
                'International Volunteer Day - Community Service'
            ];

            $descriptions = [
                'Annual celebration marking the beginning of new calendar year with traditional festivities and resolutions.',
                'National holiday honoring civil rights leader and his contributions to equality and social justice.',
                'Federal holiday celebrating the birthdays of George Washington and Abraham Lincoln with patriotic observance.',
                'Christian holiday commemorating the crucifixion of Jesus Christ observed with religious services and reflection.',
                'Christian holiday following Easter Sunday celebrating resurrection with family gatherings and traditional meals.',
                'National holiday honoring military personnel who died in service with memorial ceremonies and parades.',
                'National independence celebration with fireworks displays patriotic events and community gatherings across the country.',
                'International holiday celebrating workers rights and labor movement achievements with parades and demonstrations.',
                'Federal holiday commemorating Christopher Columbus arrival in Americas with historical reflection and cultural events.',
                'National holiday honoring military veterans for their service with ceremonies parades and community recognition.',
                'National holiday celebrating gratitude and harvest with family gatherings traditional meals and thanksgiving traditions.',
                'Religious holiday preparation for Christmas celebration with family time gift wrapping and festive activities.',
                'Major Christian holiday celebrating birth of Jesus Christ with religious services family gatherings and gift exchange.',
                'Year end celebration with parties festivities and countdown to midnight marking transition to new year.',
                'Company milestone celebration commemorating founding date with employee recognition events and organizational history reflection.',
                'Special day dedicated to recognizing employee contributions achievements and dedication with awards and appreciation events.',
                'Annual company retreat combining team building activities professional development and recreational activities in summer setting.',
                'Extended holiday break during winter season allowing employees rest relaxation and family time during cold months.',
                'Cultural celebration welcoming spring season with outdoor activities community events and renewal themed festivities.',
                'Seasonal festival celebrating autumn harvest with traditional foods community gatherings and thanksgiving themed activities.',
                'Global celebration recognizing womens achievements contributions and ongoing fight for gender equality and empowerment.',
                'Environmental awareness day promoting sustainability conservation and ecological responsibility through educational activities and green initiatives.',
                'World health organization sponsored day focusing on global health issues wellness promotion and healthcare accessibility.',
                'International labor day celebrating workers rights achievements and ongoing struggles for fair wages and working conditions.',
                'United Nations environmental day promoting awareness about environmental protection conservation and sustainable development practices.',
                'International day promoting peace conflict resolution and global harmony through educational events and community activities.',
                'Global recognition day honoring teachers educators and their vital role in society through appreciation events and educational focus.',
                'International observance promoting human rights awareness equality justice and dignity for all people worldwide.',
                'Global health awareness day focusing on HIV AIDS prevention treatment and support for affected communities.',
                'International recognition day celebrating volunteers and their contributions to communities through service and charitable activities.'
            ];

            // Create boolean arrays for realistic distribution
            $isPaidArray = array_merge(array_fill(0, 22, true), array_fill(0, 8, false));
            $isGoogleSyncArray = array_merge(array_fill(0, 12, true), array_fill(0, 18, false));
            $isOutlookSyncArray = array_merge(array_fill(0, 10, true), array_fill(0, 20, false));
            
            shuffle($isPaidArray);
            shuffle($isGoogleSyncArray);
            shuffle($isOutlookSyncArray);

            $holidays = [];
            for ($i = 0; $i < 30; $i++) {
                $startDaysAgo = 175 - ($i * 5);
                $createdDaysAgo = $startDaysAgo - 1;
                
                // Some holidays are single day, others are multi-day
                $isMultiDay = ($i % 4 === 0); // Every 4th holiday is multi-day
                $endDaysAgo = $isMultiDay ? $startDaysAgo - rand(1, 3) : $startDaysAgo;

                $holidays[] = [
                    'name' => $holidayNames[$i],
                    'start_date' => Carbon::now()->subDays($startDaysAgo)->format('Y-m-d'),
                    'end_date' => Carbon::now()->subDays($endDaysAgo)->format('Y-m-d'),
                    'holiday_type_id' => $holidayTypes[$i % count($holidayTypes)],
                    'description' => $descriptions[$i],
                    'is_paid' => $isPaidArray[$i],
                    'is_sync_google_calendar' => $isGoogleSyncArray[$i],
                    'is_sync_outlook_calendar' => $isOutlookSyncArray[$i],
                    'created_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    'updated_at' => Carbon::now()->subDays($createdDaysAgo)->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                ];
            }

            foreach ($holidays as $holiday) {
                Holiday::updateOrCreate(
                    [
                        'name' => $holiday['name'],
                        'start_date' => $holiday['start_date'],
                        'created_by' => $userId
                    ],
                    array_merge($holiday, [
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ])
                );
            }
        }
    }
}
