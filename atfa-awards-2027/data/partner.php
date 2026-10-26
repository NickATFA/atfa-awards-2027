<?php
/**
 * The 2027 Principal Partner: one partner presents the whole ATFA weekend (the Expo & Conference and the Awards).
 *
 * Read by the awards pages (atfa_awards_partner() in the main plugin file) and by the Expo page
 * (atfa_expo27_partner() in expo/src/snippet.php), so the name, logo and wording live in one place.
 * Where it shows: a "Presented by" lockup in the hero of the splash, the entries page and the Expo page, the
 * Principal Partner tile in the splash's sponsor row, a Principal Partner feature on the entries and Expo pages,
 * and on the awards dinner bands.
 *
 * Crosbe, agreed 26 October 2026 (Phil's emails with Nathan Wood and Adam Giles, and the agreement in
 * ATFA Files > 2027 > 2027 Expo > Crosbe_ATFA_2027_Sponsorship_Agreement_Final.docx).
 *
 * 'public' => false shows the partner to logged-in staff only, everywhere; true shows it to everyone.
 * Staff only for now (Nick, 26 October 2026): switch to true once Crosbe has signed and paid the deposit.
 * Return null (or delete this file) to take the partner off every page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return array(
	'name'   => 'Crosbe',
	'role'   => 'Principal Partner',
	'url'    => 'https://crosbe.com/',
	// White and blue version for the black pages, traced from Crosbe's master logo (assets/img/sponsors/README.txt).
	'logo'   => 'sponsors/crosbe-reverse.svg',
	'width'  => 3677,
	'height' => 917,
	// From crosbe.com: the CROSflow flooring range, made in Sydney, NATA-accredited R&D laboratory.
	'about'  => 'Crosbe makes the CROSflow® floor levellers, fillers and primers that go down before the timber does. Designed and made in Sydney, and developed in Crosbe\'s own NATA-accredited laboratory.',
	'public' => false,
);
