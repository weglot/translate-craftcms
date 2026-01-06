<?php

namespace WeglotLanguages;

/**
 * @phpstan-type Country = array{
 *     code: string,
 *     name: string,
 * }
 */
class Countries
{
    /** @phpstan-return array<string, Country> */
    public static function getData()
    {
         return [
            'af' => [
                'code' => 'af',
                'name' => 'Afghanistan',
            ],
            'al' => [
                'code' => 'al',
                'name' => 'Albania',
            ],
            'dz' => [
                'code' => 'dz',
                'name' => 'Algeria',
            ],
            'as' => [
                'code' => 'as',
                'name' => 'American Samoa',
            ],
            'ad' => [
                'code' => 'ad',
                'name' => 'Andorra',
            ],
            'ao' => [
                'code' => 'ao',
                'name' => 'Angola',
            ],
            'aq' => [
                'code' => 'aq',
                'name' => 'Antarctica',
            ],
            'ag' => [
                'code' => 'ag',
                'name' => 'Antigua And Barbuda',
            ],
            'ar' => [
                'code' => 'ar',
                'name' => 'Argentina',
            ],
            'am' => [
                'code' => 'am',
                'name' => 'Armenia',
            ],
            'aw' => [
                'code' => 'aw',
                'name' => 'Aruba',
            ],
            'au' => [
                'code' => 'au',
                'name' => 'Australia',
            ],
            'at' => [
                'code' => 'at',
                'name' => 'Austria',
            ],
            'az' => [
                'code' => 'az',
                'name' => 'Azerbaijan',
            ],
            'bs' => [
                'code' => 'bs',
                'name' => 'Bahamas',
            ],
            'bh' => [
                'code' => 'bh',
                'name' => 'Bahrain',
            ],
            'bd' => [
                'code' => 'bd',
                'name' => 'Bangladesh',
            ],
            'bb' => [
                'code' => 'bb',
                'name' => 'Barbados',
            ],
            'by' => [
                'code' => 'by',
                'name' => 'Belarus',
            ],
            'be' => [
                'code' => 'be',
                'name' => 'Belgium',
            ],
            'bz' => [
                'code' => 'bz',
                'name' => 'Belize',
            ],
            'bj' => [
                'code' => 'bj',
                'name' => 'Benin',
            ],
            'bm' => [
                'code' => 'bm',
                'name' => 'Bermuda',
            ],
            'bt' => [
                'code' => 'bt',
                'name' => 'Bhutan',
            ],
            'bo' => [
                'code' => 'bo',
                'name' => 'Bolivia',
            ],
            'ba' => [
                'code' => 'ba',
                'name' => 'Bosnia And Herzegovina',
            ],
            'bw' => [
                'code' => 'bw',
                'name' => 'Botswana',
            ],
            'bv' => [
                'code' => 'bv',
                'name' => 'Bouvet Island',
            ],
            'br' => [
                'code' => 'br',
                'name' => 'Brazil',
            ],
            'io' => [
                'code' => 'io',
                'name' => 'British Indian Ocean Territory',
            ],
            'bn' => [
                'code' => 'bn',
                'name' => 'Brunei',
            ],
            'bg' => [
                'code' => 'bg',
                'name' => 'Bulgaria',
            ],
            'bf' => [
                'code' => 'bf',
                'name' => 'Burkina Faso',
            ],
            'bi' => [
                'code' => 'bi',
                'name' => 'Burundi',
            ],
            'kh' => [
                'code' => 'kh',
                'name' => 'Cambodia',
            ],
            'cm' => [
                'code' => 'cm',
                'name' => 'Cameroon',
            ],
            'ca' => [
                'code' => 'ca',
                'name' => 'Canada',
            ],
            'cv' => [
                'code' => 'cv',
                'name' => 'Cape Verde',
            ],
            'ky' => [
                'code' => 'ky',
                'name' => 'Cayman Islands',
            ],
            'cf' => [
                'code' => 'cf',
                'name' => 'Central African Republic',
            ],
            'td' => [
                'code' => 'td',
                'name' => 'Chad',
            ],
            'cl' => [
                'code' => 'cl',
                'name' => 'Chile',
            ],
            'cn' => [
                'code' => 'cn',
                'name' => 'China',
            ],
            'cx' => [
                'code' => 'cx',
                'name' => 'Christmas Island',
            ],
            'cc' => [
                'code' => 'cc',
                'name' => 'Cocos (keeling) Islands',
            ],
            'co' => [
                'code' => 'co',
                'name' => 'Colombia',
            ],
            'km' => [
                'code' => 'km',
                'name' => 'Comoros',
            ],
            'cg' => [
                'code' => 'cg',
                'name' => 'Congo',
            ],
            'ck' => [
                'code' => 'ck',
                'name' => 'Cook Islands',
            ],
            'cr' => [
                'code' => 'cr',
                'name' => 'Costa Rica',
            ],
            'hr' => [
                'code' => 'hr',
                'name' => 'Croatia',
            ],
            'cu' => [
                'code' => 'cu',
                'name' => 'Cuba',
            ],
            'cy' => [
                'code' => 'cy',
                'name' => 'Cyprus',
            ],
            'cz' => [
                'code' => 'cz',
                'name' => 'Czech Republic',
            ],
            'dk' => [
                'code' => 'dk',
                'name' => 'Denmark',
            ],
            'dj' => [
                'code' => 'dj',
                'name' => 'Djibouti',
            ],
            'dm' => [
                'code' => 'dm',
                'name' => 'Dominica',
            ],
            'do' => [
                'code' => 'do',
                'name' => 'Dominican Republic',
            ],
            'tl' => [
                'code' => 'tl',
                'name' => 'East Timor',
            ],
            'ec' => [
                'code' => 'ec',
                'name' => 'Ecuador',
            ],
            'eg' => [
                'code' => 'eg',
                'name' => 'Egypt',
            ],
            'sv' => [
                'code' => 'sv',
                'name' => 'El Salvador',
            ],
            'gq' => [
                'code' => 'gq',
                'name' => 'Equatorial Guinea',
            ],
            'er' => [
                'code' => 'er',
                'name' => 'Eritrea',
            ],
            'ee' => [
                'code' => 'ee',
                'name' => 'Estonia',
            ],
            'et' => [
                'code' => 'et',
                'name' => 'Ethiopia',
            ],
            'fk' => [
                'code' => 'fk',
                'name' => 'Falkland Islands',
            ],
            'fo' => [
                'code' => 'fo',
                'name' => 'Faroe Islands',
            ],
            'fj' => [
                'code' => 'fj',
                'name' => 'Fiji Islands',
            ],
            'fi' => [
                'code' => 'fi',
                'name' => 'Finland',
            ],
            'fr' => [
                'code' => 'fr',
                'name' => 'France',
            ],
            'gf' => [
                'code' => 'gf',
                'name' => 'French Guiana',
            ],
            'pf' => [
                'code' => 'pf',
                'name' => 'French Polynesia',
            ],
            'tf' => [
                'code' => 'tf',
                'name' => 'French Southern Territories',
            ],
            'ga' => [
                'code' => 'ga',
                'name' => 'Gabon',
            ],
            'gm' => [
                'code' => 'gm',
                'name' => 'Gambia',
            ],
            'ge' => [
                'code' => 'ge',
                'name' => 'Georgia',
            ],
            'de' => [
                'code' => 'de',
                'name' => 'Germany',
            ],
            'gh' => [
                'code' => 'gh',
                'name' => 'Ghana',
            ],
            'gi' => [
                'code' => 'gi',
                'name' => 'Gibraltar',
            ],
            'gr' => [
                'code' => 'gr',
                'name' => 'Greece',
            ],
            'gl' => [
                'code' => 'gl',
                'name' => 'Greenland',
            ],
            'gd' => [
                'code' => 'gd',
                'name' => 'Grenada',
            ],
            'gp' => [
                'code' => 'gp',
                'name' => 'Guadeloupe',
            ],
            'gu' => [
                'code' => 'gu',
                'name' => 'Guam',
            ],
            'gt' => [
                'code' => 'gt',
                'name' => 'Guatemala',
            ],
            'gg' => [
                'code' => 'gg',
                'name' => 'Guernsey',
            ],
            'gn' => [
                'code' => 'gn',
                'name' => 'Guinea',
            ],
            'gw' => [
                'code' => 'gw',
                'name' => 'Guinea Bissau',
            ],
            'gy' => [
                'code' => 'gy',
                'name' => 'Guyana',
            ],
            'ht' => [
                'code' => 'ht',
                'name' => 'Haiti',
            ],
            'hm' => [
                'code' => 'hm',
                'name' => 'Heard Island And Mcdonald Islands',
            ],
            'va' => [
                'code' => 'va',
                'name' => 'Holy See (vatican City State)',
            ],
            'hn' => [
                'code' => 'hn',
                'name' => 'Honduras',
            ],
            'hk' => [
                'code' => 'hk',
                'name' => 'Hong Kong',
            ],
            'hu' => [
                'code' => 'hu',
                'name' => 'Hungary',
            ],
            'is' => [
                'code' => 'is',
                'name' => 'Iceland',
            ],
            'in' => [
                'code' => 'in',
                'name' => 'India',
            ],
            'id' => [
                'code' => 'id',
                'name' => 'Indonesia',
            ],
            'ir' => [
                'code' => 'ir',
                'name' => 'Iran',
            ],
            'iq' => [
                'code' => 'iq',
                'name' => 'Iraq',
            ],
            'ie' => [
                'code' => 'ie',
                'name' => 'Ireland',
            ],
            'im' => [
                'code' => 'im',
                'name' => 'Isle Of Man',
            ],
            'il' => [
                'code' => 'il',
                'name' => 'Israel',
            ],
            'it' => [
                'code' => 'it',
                'name' => 'Italy',
            ],
            'ci' => [
                'code' => 'ci',
                'name' => 'Ivory Coast',
            ],
            'jm' => [
                'code' => 'jm',
                'name' => 'Jamaica',
            ],
            'jp' => [
                'code' => 'jp',
                'name' => 'Japan',
            ],
            'je' => [
                'code' => 'je',
                'name' => 'Jersey',
            ],
            'jo' => [
                'code' => 'jo',
                'name' => 'Jordan',
            ],
            'kz' => [
                'code' => 'kz',
                'name' => 'Kazakhstan',
            ],
            'ke' => [
                'code' => 'ke',
                'name' => 'Kenya',
            ],
            'ki' => [
                'code' => 'ki',
                'name' => 'Kiribati',
            ],
            'kw' => [
                'code' => 'kw',
                'name' => 'Kuwait',
            ],
            'kg' => [
                'code' => 'kg',
                'name' => 'Kyrgyzstan',
            ],
            'la' => [
                'code' => 'la',
                'name' => 'Laos',
            ],
            'lv' => [
                'code' => 'lv',
                'name' => 'Latvia',
            ],
            'lb' => [
                'code' => 'lb',
                'name' => 'Lebanon',
            ],
            'ls' => [
                'code' => 'ls',
                'name' => 'Lesotho',
            ],
            'lr' => [
                'code' => 'lr',
                'name' => 'Liberia',
            ],
            'ly' => [
                'code' => 'ly',
                'name' => 'Libya',
            ],
            'li' => [
                'code' => 'li',
                'name' => 'Liechtenstein',
            ],
            'lt' => [
                'code' => 'lt',
                'name' => 'Lithuania',
            ],
            'lu' => [
                'code' => 'lu',
                'name' => 'Luxembourg',
            ],
            'mo' => [
                'code' => 'mo',
                'name' => 'Macao',
            ],
            'mg' => [
                'code' => 'mg',
                'name' => 'Madagascar',
            ],
            'mw' => [
                'code' => 'mw',
                'name' => 'Malawi',
            ],
            'my' => [
                'code' => 'my',
                'name' => 'Malaysia',
            ],
            'mv' => [
                'code' => 'mv',
                'name' => 'Maldives',
            ],
            'ml' => [
                'code' => 'ml',
                'name' => 'Mali',
            ],
            'mt' => [
                'code' => 'mt',
                'name' => 'Malta',
            ],
            'mh' => [
                'code' => 'mh',
                'name' => 'Marshall Islands',
            ],
            'mq' => [
                'code' => 'mq',
                'name' => 'Martinique',
            ],
            'mr' => [
                'code' => 'mr',
                'name' => 'Mauritania',
            ],
            'mu' => [
                'code' => 'mu',
                'name' => 'Mauritius',
            ],
            'yt' => [
                'code' => 'yt',
                'name' => 'Mayotte',
            ],
            'mx' => [
                'code' => 'mx',
                'name' => 'Mexico',
            ],
            'fm' => [
                'code' => 'fm',
                'name' => 'Micronesia Federated States Of',
            ],
            'md' => [
                'code' => 'md',
                'name' => 'Moldova',
            ],
            'mc' => [
                'code' => 'mc',
                'name' => 'Monaco',
            ],
            'mn' => [
                'code' => 'mn',
                'name' => 'Mongolia',
            ],
            'me' => [
                'code' => 'me',
                'name' => 'Montenegro',
            ],
            'ms' => [
                'code' => 'ms',
                'name' => 'Montserrat',
            ],
            'ma' => [
                'code' => 'ma',
                'name' => 'Morocco',
            ],
            'mz' => [
                'code' => 'mz',
                'name' => 'Mozambique',
            ],
            'mm' => [
                'code' => 'mm',
                'name' => 'Myanmar',
            ],
            'na' => [
                'code' => 'na',
                'name' => 'Namibia',
            ],
            'nr' => [
                'code' => 'nr',
                'name' => 'Nauru',
            ],
            'np' => [
                'code' => 'np',
                'name' => 'Nepal',
            ],
            'nl' => [
                'code' => 'nl',
                'name' => 'Netherlands',
            ],
            'nc' => [
                'code' => 'nc',
                'name' => 'New Caledonia',
            ],
            'nz' => [
                'code' => 'nz',
                'name' => 'New Zealand',
            ],
            'ni' => [
                'code' => 'ni',
                'name' => 'Nicaragua',
            ],
            'ne' => [
                'code' => 'ne',
                'name' => 'Niger',
            ],
            'ng' => [
                'code' => 'ng',
                'name' => 'Nigeria',
            ],
            'nu' => [
                'code' => 'nu',
                'name' => 'Niue',
            ],
            'nf' => [
                'code' => 'nf',
                'name' => 'Norfolk Island',
            ],
            'kp' => [
                'code' => 'kp',
                'name' => 'North Korea',
            ],
            'mk' => [
                'code' => 'mk',
                'name' => 'North Macedonia',
            ],
            'no' => [
                'code' => 'no',
                'name' => 'Norway',
            ],
            'om' => [
                'code' => 'om',
                'name' => 'Oman',
            ],
            'pk' => [
                'code' => 'pk',
                'name' => 'Pakistan',
            ],
            'pw' => [
                'code' => 'pw',
                'name' => 'Palau',
            ],
            'ps' => [
                'code' => 'ps',
                'name' => 'Palestine',
            ],
            'pa' => [
                'code' => 'pa',
                'name' => 'Panama',
            ],
            'pg' => [
                'code' => 'pg',
                'name' => 'Papua New Guinea',
            ],
            'py' => [
                'code' => 'py',
                'name' => 'Paraguay',
            ],
            'pe' => [
                'code' => 'pe',
                'name' => 'Peru',
            ],
            'ph' => [
                'code' => 'ph',
                'name' => 'Philippines',
            ],
            'pn' => [
                'code' => 'pn',
                'name' => 'Pitcairn',
            ],
            'pl' => [
                'code' => 'pl',
                'name' => 'Poland',
            ],
            'pt' => [
                'code' => 'pt',
                'name' => 'Portugal',
            ],
            'pr' => [
                'code' => 'pr',
                'name' => 'Puerto Rico',
            ],
            'qa' => [
                'code' => 'qa',
                'name' => 'Qatar',
            ],
            'fr-ca' => [
                'code' => 'fr-ca',
                'name' => 'Quebec',
            ],
            're' => [
                'code' => 're',
                'name' => 'Reunion',
            ],
            'ro' => [
                'code' => 'ro',
                'name' => 'Romania',
            ],
            'ru' => [
                'code' => 'ru',
                'name' => 'Russian Federation',
            ],
            'rw' => [
                'code' => 'rw',
                'name' => 'Rwanda',
            ],
            'sh' => [
                'code' => 'sh',
                'name' => 'Saint Helena',
            ],
            'kn' => [
                'code' => 'kn',
                'name' => 'Saint Kitts And Nevis',
            ],
            'lc' => [
                'code' => 'lc',
                'name' => 'Saint Lucia',
            ],
            'pm' => [
                'code' => 'pm',
                'name' => 'Saint Pierre And Miquelon',
            ],
            'vc' => [
                'code' => 'vc',
                'name' => 'Saint Vincent And The Grenadines',
            ],
            'ws' => [
                'code' => 'ws',
                'name' => 'Samoa',
            ],
            'sm' => [
                'code' => 'sm',
                'name' => 'San Marino',
            ],
            'st' => [
                'code' => 'st',
                'name' => 'Sao Tome And Principe',
            ],
            'sa' => [
                'code' => 'sa',
                'name' => 'Saudi Arabia',
            ],
            'sn' => [
                'code' => 'sn',
                'name' => 'Senegal',
            ],
            'rs' => [
                'code' => 'rs',
                'name' => 'Serbia',
            ],
            'sc' => [
                'code' => 'sc',
                'name' => 'Seychelles',
            ],
            'sl' => [
                'code' => 'sl',
                'name' => 'Sierra Leone',
            ],
            'sg' => [
                'code' => 'sg',
                'name' => 'Singapore',
            ],
            'sk' => [
                'code' => 'sk',
                'name' => 'Slovakia',
            ],
            'si' => [
                'code' => 'si',
                'name' => 'Slovenia',
            ],
            'sb' => [
                'code' => 'sb',
                'name' => 'Solomon Islands',
            ],
            'so' => [
                'code' => 'so',
                'name' => 'Somalia',
            ],
            'za' => [
                'code' => 'za',
                'name' => 'South Africa',
            ],
            'gs' => [
                'code' => 'gs',
                'name' => 'South Georgia And The South Sandwich Islands',
            ],
            'kr' => [
                'code' => 'kr',
                'name' => 'South Korea',
            ],
            'ss' => [
                'code' => 'ss',
                'name' => 'South Sudan',
            ],
            'es' => [
                'code' => 'es',
                'name' => 'Spain',
            ],
            'lk' => [
                'code' => 'lk',
                'name' => 'Sri Lanka',
            ],
            'sd' => [
                'code' => 'sd',
                'name' => 'Sudan',
            ],
            'sr' => [
                'code' => 'sr',
                'name' => 'Suriname',
            ],
            'sj' => [
                'code' => 'sj',
                'name' => 'Svalbard And Jan Mayen',
            ],
            'se' => [
                'code' => 'se',
                'name' => 'Sweden',
            ],
            'ch' => [
                'code' => 'ch',
                'name' => 'Switzerland',
            ],
            'sy' => [
                'code' => 'sy',
                'name' => 'Syria',
            ],
            'tj' => [
                'code' => 'tj',
                'name' => 'Tajikistan',
            ],
            'tz' => [
                'code' => 'tz',
                'name' => 'Tanzania',
            ],
            'th' => [
                'code' => 'th',
                'name' => 'Thailand',
            ],
            'cd' => [
                'code' => 'cd',
                'name' => 'The Democratic Republic Of Congo',
            ],
            'tg' => [
                'code' => 'tg',
                'name' => 'Togo',
            ],
            'tk' => [
                'code' => 'tk',
                'name' => 'Tokelau',
            ],
            'to' => [
                'code' => 'to',
                'name' => 'Tonga',
            ],
            'tt' => [
                'code' => 'tt',
                'name' => 'Trinidad And Tobago',
            ],
            'tn' => [
                'code' => 'tn',
                'name' => 'Tunisia',
            ],
            'tr' => [
                'code' => 'tr',
                'name' => 'Turkey',
            ],
            'tm' => [
                'code' => 'tm',
                'name' => 'Turkmenistan',
            ],
            'tc' => [
                'code' => 'tc',
                'name' => 'Turks And Caicos Islands',
            ],
            'tv' => [
                'code' => 'tv',
                'name' => 'Tuvalu',
            ],
            'ug' => [
                'code' => 'ug',
                'name' => 'Uganda',
            ],
            'ua' => [
                'code' => 'ua',
                'name' => 'Ukraine',
            ],
            'ae' => [
                'code' => 'ae',
                'name' => 'United Arab Emirates',
            ],
            'gb' => [
                'code' => 'gb',
                'name' => 'United Kingdom',
            ],
            'us' => [
                'code' => 'us',
                'name' => 'United States',
            ],
            'um' => [
                'code' => 'um',
                'name' => 'United States Minor Outlying Islands',
            ],
            'uy' => [
                'code' => 'uy',
                'name' => 'Uruguay',
            ],
            'uz' => [
                'code' => 'uz',
                'name' => 'Uzbekistan',
            ],
            'vu' => [
                'code' => 'vu',
                'name' => 'Vanuatu',
            ],
            've' => [
                'code' => 've',
                'name' => 'Venezuela',
            ],
            'vn' => [
                'code' => 'vn',
                'name' => 'Vietnam',
            ],
            'vg' => [
                'code' => 'vg',
                'name' => 'Virgin Islands British',
            ],
            'vi' => [
                'code' => 'vi',
                'name' => 'Virgin Islands Us',
            ],
            'gb-wls' => [
                'code' => 'gb-wls',
                'name' => 'Wales',
            ],
            'wf' => [
                'code' => 'wf',
                'name' => 'Wallis And Futuna',
            ],
            'eh' => [
                'code' => 'eh',
                'name' => 'Western Sahara',
            ],
            'ye' => [
                'code' => 'ye',
                'name' => 'Yemen',
            ],
            'zm' => [
                'code' => 'zm',
                'name' => 'Zambia',
            ],
            'zw' => [
                'code' => 'zw',
                'name' => 'Zimbabwe',
            ],
         ];
    }
}
