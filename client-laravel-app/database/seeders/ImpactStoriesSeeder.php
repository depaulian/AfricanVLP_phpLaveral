<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ImpactStory;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Str;

class ImpactStoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and organizations for the stories
        $users = User::limit(5)->get();
        $organizations = Organization::limit(3)->get();

        if ($users->isEmpty() || $organizations->isEmpty()) {
            $this->command->warn('No users or organizations found. Please seed users and organizations first.');
            return;
        }

        $stories = [
            [
                'title' => 'Feeding 500 Families During the Holiday Season',
                'summary' => 'Our volunteer team organized a massive food distribution drive that provided meals to 500 families in need during the holiday season.',
                'content' => '<p>The holiday season can be particularly challenging for families facing food insecurity. This year, our dedicated team of volunteers came together to organize one of the largest food distribution drives in our community\'s history.</p>

<p>Over the course of three weeks, we coordinated with local food banks, grocery stores, and community donors to collect over 10,000 pounds of food items. Our volunteers worked tirelessly to sort, package, and distribute these items to families who needed them most.</p>

<p>The impact was immediate and profound. We were able to provide complete holiday meals to 500 families, ensuring that children and parents alike could enjoy nutritious food during this special time of year. Many families expressed their gratitude, sharing how this support allowed them to focus on spending quality time together rather than worrying about their next meal.</p>

<p>This initiative not only addressed immediate food needs but also strengthened our community bonds. Volunteers from different backgrounds came together with a shared purpose, creating lasting friendships and a stronger sense of community solidarity.</p>

<p>The success of this program has inspired us to establish a permanent monthly food distribution program, ensuring that our support continues throughout the year.</p>',
                'story_type' => 'success',
                'story_date' => now()->subMonths(2),
                'location' => 'Downtown Community Center',
                'tags' => ['food security', 'community support', 'holiday giving', 'family assistance'],
                'impact_metrics' => [
                    ['metric_id' => 1, 'value' => 2000], // People helped (500 families × 4 average family size)
                    ['metric_id' => 4, 'value' => 1500], // Meals provided
                ],
                'is_published' => true,
                'is_featured' => true,
            ],
            [
                'title' => 'Teaching Digital Literacy to 200 Senior Citizens',
                'summary' => 'A comprehensive program that helped senior citizens navigate the digital world, connecting them with family and essential services online.',
                'content' => '<p>In today\'s increasingly digital world, many senior citizens find themselves left behind, unable to access essential services or connect with loved ones online. Our Digital Literacy for Seniors program was designed to bridge this gap and empower older adults with the skills they need to thrive in the digital age.</p>

<p>Over six months, our team of volunteer instructors worked with 200 senior citizens, providing one-on-one and small group training sessions. We covered everything from basic computer skills and internet navigation to video calling with family members and accessing online healthcare portals.</p>

<p>The transformation was remarkable. Participants who had never used a computer before were soon confidently sending emails to their grandchildren, ordering groceries online, and attending virtual medical appointments. The program not only provided practical skills but also reduced social isolation, particularly important during times when in-person gatherings were limited.</p>

<p>One of our most memorable success stories involved Margaret, an 82-year-old grandmother who learned to use video calling to stay connected with her family across the country. She went from being afraid of technology to becoming a digital advocate in her senior living community, helping other residents get online.</p>

<p>The program\'s success has led to its expansion, with additional locations now offering similar training. We\'ve also developed a peer mentorship component, where program graduates help teach new participants, creating a sustainable model for ongoing digital inclusion.</p>',
                'story_type' => 'success',
                'story_date' => now()->subMonths(4),
                'location' => 'Senior Community Centers',
                'tags' => ['digital literacy', 'senior citizens', 'education', 'technology access'],
                'impact_metrics' => [
                    ['metric_id' => 5, 'value' => 200], // Students taught
                    ['metric_id' => 8, 'value' => 48], // Training sessions conducted
                ],
                'is_published' => true,
                'is_featured' => true,
            ],
            [
                'title' => 'Planting 1,000 Trees for Urban Reforestation',
                'summary' => 'A community-wide environmental initiative that planted 1,000 native trees across urban areas to improve air quality and create green spaces.',
                'content' => '<p>Urban areas often suffer from poor air quality and lack of green spaces, affecting both environmental health and community well-being. Our Urban Reforestation Project aimed to address these challenges by bringing nature back to the city through strategic tree planting initiatives.</p>

<p>Working closely with the city\'s environmental department and local environmental groups, we identified key areas that would benefit most from increased tree coverage. These included school grounds, community parks, and residential neighborhoods with limited green space.</p>

<p>Over the course of three community planting days, more than 150 volunteers came together to plant 1,000 native trees. We chose species that were well-adapted to the local climate and would provide maximum environmental benefits, including improved air quality, reduced urban heat island effects, and habitat for local wildlife.</p>

<p>The project went beyond just planting trees. We also established an ongoing maintenance program, with volunteers committed to watering, mulching, and monitoring the health of the newly planted trees during their critical first two years of growth.</p>

<p>Early results are already visible. Air quality measurements in the targeted areas show improvement, and community members report increased use of the newly green spaces for recreation and relaxation. The project has also sparked interest in additional environmental initiatives, including community gardens and recycling programs.</p>

<p>This initiative demonstrates how collective action can create lasting environmental change while bringing communities together around a shared vision of a greener, healthier future.</p>',
                'story_type' => 'success',
                'story_date' => now()->subMonths(3),
                'location' => 'Various Urban Locations',
                'tags' => ['environment', 'reforestation', 'air quality', 'community action'],
                'impact_metrics' => [
                    ['metric_id' => 9, 'value' => 1000], // Trees planted
                    ['metric_id' => 12, 'value' => 2500], // Carbon footprint reduced (estimated kg CO2)
                ],
                'is_published' => true,
                'is_featured' => true,
            ],
            [
                'title' => 'Overcoming Challenges in Rural Healthcare Access',
                'summary' => 'How our mobile health clinic overcame logistical challenges to provide essential healthcare services to remote rural communities.',
                'content' => '<p>Providing healthcare services to remote rural communities presents unique challenges, from difficult terrain and limited infrastructure to resource constraints and coordination complexities. Our mobile health clinic program faced all of these obstacles but found innovative solutions to ensure no community was left without access to essential healthcare.</p>

<p>The initial challenge was transportation. Many of the communities we wanted to serve were accessible only by unpaved roads that became impassable during rainy seasons. We partnered with local transportation companies and invested in all-terrain vehicles capable of navigating difficult conditions year-round.</p>

<p>Staffing was another hurdle. Healthcare professionals were often reluctant to travel to remote areas for short-term assignments. We addressed this by creating attractive volunteer packages that included professional development opportunities and partnering with medical schools to offer clinical rotation credits for students participating in the program.</p>

<p>Equipment and supply logistics required creative solutions. We developed portable, solar-powered medical equipment and established supply caches in strategic locations. Local community leaders were trained to maintain basic supplies between visits.</p>

<p>Despite these challenges, the program has been remarkably successful. Over the past year, we\'ve conducted 120 health screenings, administered 300 vaccinations, and provided health education to over 800 community members across 15 remote villages.</p>

<p>The key to our success has been building strong partnerships with local communities, who now actively participate in planning and supporting our visits. This collaborative approach has not only improved healthcare access but also strengthened community capacity for health promotion and disease prevention.</p>',
                'story_type' => 'challenge',
                'story_date' => now()->subMonths(1),
                'location' => 'Remote Rural Communities',
                'tags' => ['healthcare access', 'rural communities', 'mobile clinic', 'community partnership'],
                'impact_metrics' => [
                    ['metric_id' => 13, 'value' => 120], // Health screenings conducted
                    ['metric_id' => 14, 'value' => 300], // Vaccinations administered
                    ['metric_id' => 15, 'value' => 25], // Health education sessions
                ],
                'is_published' => true,
                'is_featured' => false,
            ],
            [
                'title' => 'Innovation in Disaster Response: Community Resilience Network',
                'summary' => 'An innovative approach to disaster preparedness that created a network of trained community volunteers ready to respond to emergencies.',
                'content' => '<p>Traditional disaster response often relies on external agencies that may take time to reach affected communities. Our Community Resilience Network represents an innovative approach that empowers local residents to be first responders in their own neighborhoods, creating a more resilient and self-sufficient community.</p>

<p>The program began with comprehensive training for community volunteers in basic emergency response skills, including first aid, search and rescue techniques, emergency communication, and disaster assessment. We partnered with professional emergency services to ensure our training met official standards.</p>

<p>What makes this program innovative is its use of technology and community mapping. We developed a mobile app that allows trained volunteers to quickly report emergencies, coordinate response efforts, and access real-time information about available resources and needs in their area.</p>

<p>The network is organized into neighborhood clusters, each with designated leaders and specific roles during emergencies. Regular drills and exercises keep skills sharp and identify areas for improvement. The program also includes a community education component, teaching all residents basic preparedness skills.</p>

<p>The network\'s effectiveness was proven during a recent severe storm that caused widespread power outages and flooding. Our trained volunteers were able to conduct immediate damage assessments, coordinate evacuation of vulnerable residents, and establish communication with emergency services, significantly reducing response time and potentially saving lives.</p>

<p>The success of this model has attracted attention from other communities and emergency management agencies. We\'re now working to replicate the program in neighboring areas and have been invited to present our approach at national disaster preparedness conferences.</p>',
                'story_type' => 'innovation',
                'story_date' => now()->subWeeks(3),
                'location' => 'Citywide Network',
                'tags' => ['disaster preparedness', 'community resilience', 'emergency response', 'innovation'],
                'impact_metrics' => [
                    ['metric_id' => 1, 'value' => 500], // People helped (through training and response)
                    ['metric_id' => 8, 'value' => 36], // Training sessions conducted
                ],
                'is_published' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($stories as $index => $storyData) {
            $author = $users->random();
            $organization = $organizations->random();
            $volunteer = $users->random();

            ImpactStory::create([
                'title' => $storyData['title'],
                'slug' => Str::slug($storyData['title']),
                'summary' => $storyData['summary'],
                'content' => $storyData['content'],
                'author_id' => $author->id,
                'volunteer_id' => $volunteer->id,
                'organization_id' => $organization->id,
                'impact_metrics' => $storyData['impact_metrics'],
                'tags' => $storyData['tags'],
                'story_type' => $storyData['story_type'],
                'story_date' => $storyData['story_date'],
                'location' => $storyData['location'],
                'is_published' => $storyData['is_published'],
                'is_featured' => $storyData['is_featured'],
                'published_at' => $storyData['is_published'] ? $storyData['story_date'] : null,
                'published_by' => $storyData['is_published'] ? $author->id : null,
                'views_count' => rand(50, 500),
                'likes_count' => rand(5, 50),
                'shares_count' => rand(1, 15),
                'allow_comments' => true,
            ]);
        }

        $this->command->info('Impact stories seeded successfully!');
    }
}