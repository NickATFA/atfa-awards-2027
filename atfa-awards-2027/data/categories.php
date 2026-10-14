<?php
/**
 * The twenty 2027 awards: what goes in, the rules and example photos from 2025.
 *
 * Source: Phil's 2027 Entry Guidance, the Entry Terms & Conditions and the Board paper. The "what goes in" and
 * "not this one" lines are plain-English guidance written for the site; they do not replace the T&Cs.
 *
 * Streams: member (member voted project award), panel (expert panel project award), excellence, newcomers, major.
 * Example photos live in assets/img/examples/ and are 2025 entries. Winners and runners-up are named; other entries
 * stay anonymous, as they were for voting.
 *
 * Sponsors: every award carries a "Proudly sponsored by" slot. Add 'sponsor' => array( 'name' => 'König', 'img' =>
 * 'sponsors/konig.png', 'url' => 'https://...' ) to a category once its sponsor is confirmed (logo files go in
 * assets/img/sponsors/); without it the slot shows a placeholder. Confirmed sponsors also appear in the category
 * sponsors strip on both pages. Floor of the Year is sponsored by ATFA itself.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	/* ------------------------------------------------------------------ member voted */

	'res-tg' => array(
		'name'   => 'Residential Tongue & Groove Timber Floor',
		'full'   => 'Residential Tongue & Groove Timber Floor of the Year',
		'stream' => 'member',
		'lead'   => 'For a tongue and groove timber floor in a residential project.',
		'what'   => 'A solid tongue and groove timber floor in a home: a house, apartment, townhouse or holiday home. Boards laid on site, then sanded and finished on site. This was the Residential Solid category in 2025.',
		'not'    => 'Prefinished engineered boards and hybrid flooring go in Residential Prefinished & Hybrid. A stair goes in Stair Project. Parquetry has its own category. Recycled timber goes to Recycled / Restoration.',
		'examples' => array(
			array( 'RSST-25-30' ),
			array( 'RSST-25-15' ),
			array( 'RSST-25-07' ),
			array( 'RSST-25-35' ),
		),
	),

	'com-tg' => array(
		'name'   => 'Commercial / Industrial Tongue & Groove Timber Floor',
		'full'   => 'Commercial / Industrial Tongue & Groove Timber Floor of the Year',
		'stream' => 'member',
		'lead'   => 'For a tongue and groove timber floor in a commercial or industrial project.',
		'what'   => 'A solid tongue and groove timber floor in a commercial or industrial space: offices, shops, hospitality, sports courts, halls, churches and public buildings. Boards laid, sanded and finished on site. This was the Commercial or Industrial Solid category in 2025.',
		'not'    => 'Prefinished and hybrid commercial floors go in Commercial Prefinished & Hybrid. A floor in a school or learning environment cannot be member voted, but can be entered in an expert panel category it fits, such as Technical System or Sanded & Finished.',
		'examples' => array(
			array( 'CIST-25-03', 'Beaver Flooring', '2025 winner, Commercial / Industrial Solid' ),
			array( 'CIST-25-11' ),
			array( 'CIST-25-04' ),
			array( 'CIST-25-01' ),
		),
	),

	'stair' => array(
		'name'   => 'Stair Project',
		'full'   => 'Stair Project of the Year',
		'stream' => 'member',
		'lead'   => 'For a completed stair project, including solid timber or prefinished stairs.',
		'what'   => 'A finished stair in any timber system: solid timber or prefinished treads, straight, curved or spiral, in a home or a commercial building. One category now covers both of the 2025 stair categories.',
		'not'    => 'If the stair is a small part of a bigger floor project, enter the project once, in the category that best reflects the main work, and mention the stair in your summary.',
		'examples' => array(
			array( 'PFSR-25-13', 'Know-How Flooring', '2025 winner, Prefinished Stair' ),
			array( 'SLST-25-03' ),
			array( 'PFSR-25-22' ),
			array( 'PFSR-25-38' ),
		),
	),

	'res-pre' => array(
		'name'   => 'Residential Prefinished & Hybrid Floor',
		'full'   => 'Residential Prefinished & Hybrid Floor of the Year',
		'stream' => 'member',
		'lead'   => 'For a prefinished or hybrid floor in a residential project. Hybrid flooring is included in this category.',
		'what'   => 'A prefinished engineered or prefinished solid floor, or a hybrid or composite floor, in a home. One category now covers the 2025 Residential Prefinished category and the residential hybrid entries.',
		'not'    => 'Boards sanded and finished on site go in Residential Tongue & Groove. Commercial prefinished and hybrid floors go in Commercial Prefinished & Hybrid.',
		'examples' => array(
			array( 'RSPF-25-13' ),
			array( 'HYBD-25-11', 'Titan Timber Flooring', '2025 winner, Hybrid & Composite' ),
			array( 'RSPF-25-51' ),
			array( 'RSPF-25-02' ),
		),
	),

	'com-pre' => array(
		'name'   => 'Commercial Prefinished & Hybrid Floor',
		'full'   => 'Commercial Prefinished & Hybrid Floor of the Year',
		'stream' => 'member',
		'lead'   => 'For a prefinished or hybrid floor in a commercial project. Hybrid flooring is included in this category.',
		'what'   => 'A prefinished engineered, prefinished solid, hybrid or composite floor in a commercial or industrial space: offices, retail, hospitality, showrooms, gyms and public buildings. One category now covers the 2025 Commercial Prefinished category and the commercial hybrid entries.',
		'not'    => 'Boards sanded and finished on site go in Commercial / Industrial Tongue & Groove. A floor in a school or learning environment cannot be member voted, but can be entered in an expert panel category it fits.',
		'examples' => array(
			array( 'CIPF-25-04', 'Colab Floor', '2025 winner, Commercial / Industrial Prefinished' ),
			array( 'CIPF-25-12', 'Woodworks Timber Flooring', '2025 runner-up, Commercial / Industrial Prefinished' ),
			array( 'HYBD-25-04' ),
			array( 'CIPF-25-07' ),
		),
	),

	'deck' => array(
		'name'   => 'Deck / External Timber Project',
		'full'   => 'Deck / External Timber Project of the Year',
		'stream' => 'member',
		'lead'   => 'For a completed deck or external timber project.',
		'what'   => 'A deck or any other external timber project: decks, pool surrounds, boardwalks, outdoor stairs and platforms, in a home or a commercial setting.',
		'not'    => 'Internal floors go in their floor category, even when the same project includes a deck. Enter each project once.',
		'examples' => array(
			array( 'DECK-25-08', 'NJH Flooring', '2025 Deck of the Year and overall Floor of the Year' ),
			array( 'DECK-25-02' ),
			array( 'DECK-25-01' ),
			array( 'DECK-25-07' ),
		),
	),

	'parquetry' => array(
		'name'   => 'Parquetry Floor',
		'full'   => 'Parquetry Floor of the Year',
		'stream' => 'member',
		'new'    => true,
		'lead'   => 'For a completed parquetry floor.',
		'what'   => 'A parquetry floor in any pattern: herringbone, chevron, basket weave, Versailles panels, mosaic, hexagon or a custom layout. Solid or prefinished, in a home or a commercial space. New for 2027.',
		'not'    => 'A plank floor with a parquetry border or one feature panel belongs in its floor category, or in Design Feature Floor if the design is the point of the project.',
		'examples' => array(
			array( 'IDPF-25-08', 'Artistic Wood Floor', '2025 winner, Innovative Design Prefinished' ),
			array( 'IDST-25-04', 'Platinum Flooring Solutions', '2025 runner-up, Innovative Design Solid' ),
			array( 'IDPF-25-06' ),
			array( 'RSST-25-35' ),
		),
	),

	'design' => array(
		'name'   => 'Design Feature Floor',
		'full'   => 'Design Feature Floor of the Year',
		'stream' => 'member',
		'lead'   => 'For a design feature floor. Explain the design intent and how it was achieved.',
		'what'   => 'A floor where the design is the point: inlays, borders, mixed species or widths, curves, logos, lighting or other features built into the floor. One category now covers both of the 2025 Innovative Design categories.',
		'not'    => 'A standard parquetry pattern goes in Parquetry Floor. Timber work that is not a floor goes in Avant Garde.',
		'extra'  => array( 'Explain the design intent and how it was achieved.' ),
		'examples' => array(
			array( 'IDST-25-01', 'Access Timber Flooring', '2025 winner, Innovative Design Solid' ),
			array( 'IDPF-25-09' ),
			array( 'IDST-25-06' ),
			array( 'IDPF-25-11' ),
		),
	),

	'avant' => array(
		'name'   => 'Avant Garde Award',
		'full'   => 'Avant Garde Award',
		'stream' => 'member',
		'lead'   => 'For a project entered in the Avant Garde category.',
		'what'   => 'Timber work that does not fit a floor category: feature walls, ceilings, joinery, furniture, art pieces and experimental installations by a flooring business. In 2025 this was an Excellence award; for 2027 it is a member-voted project category.',
		'not'    => 'A floor with a strong design belongs in Design Feature Floor. A stair belongs in Stair Project.',
		'examples' => array(
			array( 'AVGD-25-01' ),
			array( 'AVGD-25-04' ),
			array( 'AVGD-25-07' ),
			array( 'AVGD-25-09' ),
		),
	),

	/* ------------------------------------------------------------------ expert panel */

	'recycled' => array(
		'name'   => 'Recycled / Restoration Project',
		'full'   => 'Recycled / Restoration Project of the Year',
		'stream' => 'panel',
		'lead'   => 'For a recycled timber or restoration project. Include before-and-after images where reasonably available.',
		'what'   => 'A floor made from recycled or reclaimed timber, or the restoration of an existing timber floor: repairs, matching new boards to old, bringing a heritage floor back. This was the Recycled Solid category in 2025, widened to include restoration.',
		'not'    => 'Re-sanding and coating an existing floor with no restoration work goes in Sanded & Finished Floor.',
		'extra'  => array( 'Include before and after photos where you reasonably can.' ),
		'examples' => array(
			array( 'RCYC-25-06', 'NJH Flooring', '2025 winner, Recycled Solid' ),
			array( 'RCYC-25-03' ),
			array( 'RCYC-25-02' ),
			array( 'RCYC-25-04' ),
		),
	),

	'sanded' => array(
		'name'   => 'Sanded & Finished Floor',
		'full'   => 'Sanded & Finished Floor of the Year',
		'stream' => 'panel',
		'lead'   => 'For a sanded and finished floor. Include the starting condition, preparation process and finishing system.',
		'what'   => 'The sanding and finishing of an existing timber floor, judged on the preparation and the finish rather than the installation. Show the floor you started with, what you did to it and the finishing system you used.',
		'not'    => 'A new floor you also installed goes in its floor category. Major repairs or reclaimed timber go in Recycled / Restoration.',
		'extra'  => array( 'Include the starting condition, your preparation process and the finishing system.' ),
		'examples' => array(
			array( 'SNDF-12055' ),
			array( 'SNDF-12292' ),
			array( 'SNDF-12323' ),
			array( 'SNDF-12218' ),
		),
	),

	'technical' => array(
		'name'   => 'Technical System / Complex Installation',
		'full'   => 'Technical System / Complex Installation of the Year',
		'stream' => 'panel',
		'new'    => true,
		'lead'   => 'For a project involving a technical system or complex installation.',
		'what'   => 'A project where the technical challenge is the story: sprung sports floors, acoustic or raised systems, difficult substrates, moisture or level problems, heritage constraints, large or staged commercial installs, unusual fixing or heating systems. New for 2027.',
		'not'    => 'If the finished look is the main story and the install was routine, enter the floor category instead.',
		'extra'  => array( 'Include plans, sections, substrate details, problem-solving notes or site progress photos where relevant.' ),
		'examples' => array(
			array( 'CIST-25-04' ),
			array( 'RSPF-25-44' ),
			array( 'DECK-25-02' ),
			array( 'CIPF-25-07' ),
		),
	),

	/* ------------------------------------------------------------------ excellence */

	'apprentice' => array(
		'name'     => 'Apprentice / Trainee of the Year',
		'full'     => 'Apprentice / Trainee of the Year',
		'stream'   => 'excellence',
		'lead'     => 'For an apprentice or trainee in a member business.',
		'what'     => 'An apprentice or trainee working in an ATFA member business. Write a nomination statement about their achievements and back it up with training information, work examples, references or testimonials.',
		'evidence' => 'training information, work examples, references or testimonials',
	),

	'employee' => array(
		'name'     => 'Employee of the Year',
		'full'     => 'Employee of the Year',
		'stream'   => 'excellence',
		'lead'     => 'For an employee of a member business.',
		'what'     => 'An employee of an ATFA member business who has made a real contribution this year. Write a nomination statement about their achievements and contribution and back it up with work examples, references or testimonials.',
		'evidence' => 'work examples, references or testimonials',
	),

	'contractor' => array(
		'name'     => 'Contractor of the Year',
		'full'     => 'Contractor of the Year',
		'stream'   => 'excellence',
		'lead'     => 'For a contracting business.',
		'what'     => 'A flooring contracting business, judged on the business rather than a single floor. Write a nomination statement about its achievements and back it up with project examples, references or testimonials.',
		'evidence' => 'project examples, references or testimonials',
	),

	'retailer' => array(
		'name'     => 'Retailer / Showroom of the Year',
		'full'     => 'Retailer / Showroom of the Year',
		'stream'   => 'excellence',
		'lead'     => 'For a retail store or showroom.',
		'what'     => 'A flooring retailer or showroom. Write a nomination statement about its achievements and back it up with business information, examples and testimonials.',
		'evidence' => 'business information, examples and testimonials',
	),

	'distributor' => array(
		'name'     => 'Distributor / Supplier of the Year',
		'full'     => 'Distributor / Supplier of the Year',
		'stream'   => 'excellence',
		'lead'     => 'For a distributor or supplier.',
		'what'     => 'A distributor or supplier to the timber flooring industry. Write a nomination statement about its achievements and back it up with business information, examples and testimonials.',
		'evidence' => 'business information, examples and testimonials',
	),

	'innovation' => array(
		'name'     => 'Innovation / Product Excellence',
		'full'     => 'Innovation / Product Excellence Award',
		'stream'   => 'excellence',
		'lead'     => 'For an innovation or a product.',
		'what'     => 'A product, system or innovation that has made a difference to the industry. Write a nomination statement about what it is and what it changes, and back it up with product details, evidence of achievement, images or testimonials.',
		'evidence' => 'product details, evidence of achievement, images or testimonials',
	),

	/* ------------------------------------------------------------------ newcomers and major */

	'newcomers' => array(
		'name'   => 'Newcomers Award',
		'full'   => 'Newcomers Award',
		'stream' => 'newcomers',
		'new'    => true,
		'lead'   => 'Optional consideration alongside an entry in any member-voted project category.',
		'what'   => 'For members in their first five years of ATFA membership who have never won an ATFA award or been a runner-up. There is no separate entry: tick the Newcomers box on any member-voted project entry and the expert panel considers that entry for this award as well.',
		'rules'  => array(
			'Tick the Newcomers Award box on your entry form in any member-voted project category. Consideration is not automatic.',
			'You must be within your first five years of ATFA membership at the entry closing date and must never have won an ATFA award or been a runner-up.',
			'Confirm your membership start date and previous award history. ATFA checks eligibility against its records before the awards night.',
			'No separate project submission. Your entry must meet all the rules of the category it is entered in, and you do not need to win or be a runner-up in that category.',
			'The expert panel selects the winner from the eligible opted-in entries, and may name a runner-up if it considers that appropriate in the year.',
		),
	),

	'foty' => array(
		'name'   => 'Floor of the Year',
		'full'   => 'ATFA Floor of the Year',
		'stream' => 'major',
		'sponsor' => array( 'name' => 'ATFA', 'img' => 'atfa-logo-white.png', 'url' => 'https://www.atfa.com.au/', 'atfa' => true ),
		'lead'   => 'Overall recognition selected from eligible project award category winners and runners-up.',
		'what'   => 'The top floor of the year, chosen from the winners and runners-up across the twelve project categories. Nothing extra to enter: every project entry is in the running.',
		'rules'  => array(
			'No separate project entry is required.',
			'Projects must comply with the rules of the category in which they were entered.',
			'One overall winner and one overall runner-up are selected through the process determined by ATFA.',
		),
	),
);
