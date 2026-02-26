<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Section;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\TestSectionRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TestModuleSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        QuestionOption::truncate();
        Question::truncate();
        TestSectionRule::truncate();
        TestSection::truncate();
        Test::truncate();
        Section::truncate();
        Schema::enableForeignKeyConstraints();

        $sections = ['Personality', 'Chemistry', 'GK', 'Reasoning'];
        $sectionIds = [];
        foreach ($sections as $name) {
            $section = Section::create(['name' => $name]);
            $sectionIds[$name] = $section->id;
        }

        $scaleOptions = [
            ['Strongly Disagree', 1],
            ['Disagree', 2],
            ['Neutral', 3],
            ['Agree', 4],
            ['Strongly Agree', 5],
        ];

        /*
        | PERSONALITY – 15 scale (5 easy + 5 medium + 5 hard)
        */
        $personalityQuestions = [
            'Aapka mind kaise kaam karta hai?',
            'Decision lene mein aap kaise rehte hain?',
            'Nayi cheezein seekhne mein aapka interest kaisa hai?',
            'Stress ya pressure mein aap kaise react karte hain?',
            'Doosron ke saath kaam karte waqt aap kaise feel karte hain?',
            'Planning aur routine follow karna aapko kaisa lagta hai?',
            'Aap apne aap ko kaise judge karte hain?',
            'Risk lena aapke liye kaisa hai?',
            'Aap apni feelings ko kaise express karte hain?',
            'Goals set karke unhe achieve karna aapke liye kaisa hai?',
            'Nayi jagah ya naye logon ke beech aap kaise rehte hain?',
            'Failure ke baad aap kaise react karte hain?',
            'Time management aapke liye kitna important hai?',
            'Team mein kaam karna aapko kaisa lagta hai?',
            'Aap apne decisions pe kitna confident rehte hain?',
        ];

        foreach ($personalityQuestions as $i => $qText) {
            $difficulty = $i < 5 ? 'easy' : ($i < 10 ? 'medium' : 'hard');
            $q = Question::create([
                'section_id' => $sectionIds['Personality'],
                'question_text' => $qText,
                'question_type' => 'scale',
                'difficulty' => $difficulty,
                'status' => 1,
            ]);
            foreach ($scaleOptions as $idx => $opt) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'option_text' => $opt[0],
                    'score_value' => $opt[1],
                    'sequence' => $idx + 1,
                ]);
            }
        }

        /*
        | CHEMISTRY – 15 MCQ (5 easy + 5 medium + 5 hard)
        */
        $chemistryAll = [
            ['text' => 'Water ka chemical formula kya hai?', 'options' => ['H2O', 'CO2', 'NaCl', 'O2'], 'correct' => 0, 'diff' => 'easy'],
            ['text' => 'Sodium chloride ka formula kya hai?', 'options' => ['NaCl', 'KCl', 'CaCl2', 'MgCl2'], 'correct' => 0, 'diff' => 'easy'],
            ['text' => 'Carbon dioxide ka formula kya hai?', 'options' => ['CO', 'CO2', 'C2O', 'CaO'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'Methane ka formula kya hai?', 'options' => ['CH4', 'C2H6', 'C3H8', 'CH2'], 'correct' => 0, 'diff' => 'easy'],
            ['text' => 'Oxygen gas ka formula kya hai?', 'options' => ['O', 'O2', 'O3', 'O4'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'Sulphuric acid ka formula kya hai?', 'options' => ['HCl', 'H2SO4', 'HNO3', 'H3PO4'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => 'Ammonia ka formula kya hai?', 'options' => ['NH2', 'NH3', 'NH4', 'N2H'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => 'Hydrochloric acid ka formula kya hai?', 'options' => ['H2Cl', 'HCl', 'HClO', 'HClO2'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => 'Calcium carbonate ka formula kya hai?', 'options' => ['CaCO3', 'CaO', 'Ca(OH)2', 'CaCl2'], 'correct' => 0, 'diff' => 'medium'],
            ['text' => 'Nitric acid ka formula kya hai?', 'options' => ['HNO2', 'HNO3', 'H2NO3', 'NO3'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => 'Glucose ka molecular formula kya hai?', 'options' => ['C6H12O6', 'C12H22O11', 'C5H10O5', 'CH2O'], 'correct' => 0, 'diff' => 'hard'],
            ['text' => 'Ethanol (alcohol) ka formula kya hai?', 'options' => ['CH3OH', 'C2H5OH', 'C3H7OH', 'CH3CH2OH'], 'correct' => 1, 'diff' => 'hard'],
            ['text' => 'Sulphur dioxide ka formula kya hai?', 'options' => ['SO', 'SO2', 'SO3', 'S2O'], 'correct' => 1, 'diff' => 'hard'],
            ['text' => 'Sodium hydroxide ka formula kya hai?', 'options' => ['NaOH', 'KOH', 'Na2O', 'NaH'], 'correct' => 0, 'diff' => 'hard'],
            ['text' => 'Acetic acid (vinegar) ka formula kya hai?', 'options' => ['CH3COOH', 'HCOOH', 'C2H5COOH', 'HCl'], 'correct' => 0, 'diff' => 'hard'],
        ];
        foreach ($chemistryAll as $qData) {
            $diff = $qData['diff'] ?? 'medium';
            $q = Question::create([
                'section_id' => $sectionIds['Chemistry'],
                'question_text' => $qData['text'],
                'question_type' => 'mcq',
                'difficulty' => $diff,
                'status' => 1,
            ]);
            foreach ($qData['options'] as $idx => $optText) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'option_text' => $optText,
                    'is_correct' => $idx === $qData['correct'],
                    'sequence' => $idx + 1,
                ]);
            }
        }

        /*
        | GK – 15 MCQ (5 easy + 5 medium + 5 hard)
        */
        $gkAll = [
            ['text' => 'India ka national animal kaun sa hai?', 'options' => ['Lion', 'Tiger', 'Elephant', 'Peacock'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'India ki capital kya hai?', 'options' => ['Mumbai', 'Kolkata', 'New Delhi', 'Chennai'], 'correct' => 2, 'diff' => 'easy'],
            ['text' => 'Sun rise kis disha mein hota hai?', 'options' => ['West', 'North', 'East', 'South'], 'correct' => 2, 'diff' => 'easy'],
            ['text' => 'India ka national flower kya hai?', 'options' => ['Rose', 'Lotus', 'Jasmine', 'Sunflower'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'India ka national flag kitne rang ka hai?', 'options' => ['2', '3', '4', '5'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'World ka sabse bada ocean kaun sa hai?', 'options' => ['Atlantic', 'Indian', 'Pacific', 'Arctic'], 'correct' => 2, 'diff' => 'medium'],
            ['text' => 'Hindi alphabet mein total kitne letters hote hain?', 'options' => ['44', '46', '52', '50'], 'correct' => 2, 'diff' => 'medium'],
            ['text' => 'Gravity discover kisne kiya tha?', 'options' => ['Einstein', 'Newton', 'Galileo', 'Darwin'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => 'Earth par sabse badi continent kaun si hai?', 'options' => ['Africa', 'Europe', 'Asia', 'Australia'], 'correct' => 2, 'diff' => 'medium'],
            ['text' => 'Human body mein kitni bones hoti hain (approx)?', 'options' => ['186', '206', '226', '246'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => 'India ka Constitution kab lagu hua?', 'options' => ['1947', '1949', '1950', '1952'], 'correct' => 2, 'diff' => 'hard'],
            ['text' => 'UNO ka headquarter kahan hai?', 'options' => ['London', 'Paris', 'New York', 'Geneva'], 'correct' => 2, 'diff' => 'hard'],
            ['text' => 'First PM of India kaun the?', 'options' => ['Rajendra Prasad', 'Jawaharlal Nehru', 'Sardar Patel', 'Maulana Azad'], 'correct' => 1, 'diff' => 'hard'],
            ['text' => 'Red Fort kahan hai?', 'options' => ['Agra', 'Delhi', 'Jaipur', 'Mumbai'], 'correct' => 1, 'diff' => 'hard'],
            ['text' => 'Longest river of India kaun si hai?', 'options' => ['Yamuna', 'Ganga', 'Brahmaputra', 'Godavari'], 'correct' => 1, 'diff' => 'hard'],
        ];
        foreach ($gkAll as $qData) {
            $q = Question::create([
                'section_id' => $sectionIds['GK'],
                'question_text' => $qData['text'],
                'question_type' => 'mcq',
                'difficulty' => $qData['diff'],
                'status' => 1,
            ]);
            foreach ($qData['options'] as $idx => $optText) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'option_text' => $optText,
                    'is_correct' => $idx === $qData['correct'],
                    'sequence' => $idx + 1,
                ]);
            }
        }

        /*
        | REASONING – 15 MCQ (5 easy + 5 medium + 5 hard)
        */
        $reasoningAll = [
            ['text' => '2, 4, 6, 8, ? – next number kya hoga?', 'options' => ['9', '10', '11', '12'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'A, C, E, G, ? – next letter kya hoga?', 'options' => ['H', 'I', 'J', 'K'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'Dog : Bark :: Cow : ?', 'options' => ['Meow', 'Moo', 'Roar', 'Hiss'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'Pen : Write :: Knife : ?', 'options' => ['Cut', 'Eat', 'Sharp', 'Kitchen'], 'correct' => 0, 'diff' => 'easy'],
            ['text' => '3, 6, 9, 12, ? – next number?', 'options' => ['14', '15', '16', '18'], 'correct' => 1, 'diff' => 'easy'],
            ['text' => 'Monday : Week :: January : ?', 'options' => ['Month', 'Year', 'Day', 'Season'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => 'Book : Read :: Song : ?', 'options' => ['Write', 'Listen', 'Play', 'Sing'], 'correct' => 3, 'diff' => 'medium'],
            ['text' => '5, 10, 15, 20, ? – next number?', 'options' => ['22', '24', '25', '30'], 'correct' => 2, 'diff' => 'medium'],
            ['text' => '1, 4, 9, 16, ? – next number?', 'options' => ['20', '22', '24', '25'], 'correct' => 3, 'diff' => 'medium'],
            ['text' => 'If all roses are flowers and some flowers are red, then:', 'options' => ['All roses are red', 'Some roses may be red', 'No rose is red', 'All flowers are roses'], 'correct' => 1, 'diff' => 'medium'],
            ['text' => '2, 3, 5, 7, 11, ? – next prime number?', 'options' => ['12', '13', '14', '15'], 'correct' => 1, 'diff' => 'hard'],
            ['text' => 'A is B’s sister. C is B’s mother. D is C’s father. E is D’s mother. Then A is E ka?', 'options' => ['Daughter', 'Granddaughter', 'Sister', 'Mother'], 'correct' => 1, 'diff' => 'hard'],
            ['text' => '1, 1, 2, 3, 5, 8, ? – next number (Fibonacci)?', 'options' => ['11', '12', '13', '14'], 'correct' => 2, 'diff' => 'hard'],
            ['text' => 'Day : Night :: Summer : ?', 'options' => ['Winter', 'Spring', 'Hot', 'Cold'], 'correct' => 0, 'diff' => 'hard'],
            ['text' => 'All cats are animals. Some animals are black. Conclusion?', 'options' => ['All cats are black', 'Some cats may be black', 'No cat is black', 'All black are cats'], 'correct' => 1, 'diff' => 'hard'],
        ];
        foreach ($reasoningAll as $qData) {
            $q = Question::create([
                'section_id' => $sectionIds['Reasoning'],
                'question_text' => $qData['text'],
                'question_type' => 'mcq',
                'difficulty' => $qData['diff'],
                'status' => 1,
            ]);
            foreach ($qData['options'] as $idx => $optText) {
                QuestionOption::create([
                    'question_id' => $q->id,
                    'option_text' => $optText,
                    'is_correct' => $idx === $qData['correct'],
                    'sequence' => $idx + 1,
                ]);
            }
        }

        /*
        | MASTER TEST – 15 questions per section (5 easy + 5 medium + 5 hard each) = 60 total
        */
        $test = Test::create([
            'title' => 'Career Aptitude Full Demo Test',
            'intro' => 'Personality, Chemistry, GK aur Reasoning – har section mein 15 sawal.',
            'instructions' => 'No negative marking. All questions compulsory. Har section: 5 easy, 5 medium, 5 hard.',
            'difficulty' => 'mixed',
            'total_time' => 90,
            'status' => 'draft',
        ]);

        $sequence = 1;
        foreach ($sectionIds as $sectionId) {
            $testSection = TestSection::create([
                'test_id' => $test->id,
                'section_id' => $sectionId,
                'total_questions' => 15,
                'marks_per_question' => 1,
                'section_time' => 20,
                'sequence' => $sequence++,
            ]);
            TestSectionRule::insert([
                ['test_section_id' => $testSection->id, 'difficulty' => 'easy', 'question_count' => 5],
                ['test_section_id' => $testSection->id, 'difficulty' => 'medium', 'question_count' => 5],
                ['test_section_id' => $testSection->id, 'difficulty' => 'hard', 'question_count' => 5],
            ]);
        }
    }
}
