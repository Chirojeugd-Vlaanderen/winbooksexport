<?php
/*
  be.chiro.civi.winbooksexport - Contribution export format for Winbooks.
  Copyright (C) 2016  Chirojeugd-Vlaanderen vzw

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Testable shared functionality that does not depend on the API.
 */
class CRM_Winbooksexport_Helper {
  /**
   * Generates OGM based on 'bron boekhouding' and invoice number.
   *
   * @param string $bron_boekhouding, 'AANSLUIT' or 'CURSUS' (default)
   * @param int $factuurnummer
   * @param bool $human_readable
   * @return string
   *
   * @throws \Exception
   */
  public static function ogm($bron_boekhouding, $factuurnummer, $human_readable = TRUE) {
    if (!isset($factuurnummer) || trim($factuurnummer)==='') {
      throw new Exception('Leeg factuurnummer, dat zou niet mogen.');
    }
    $laatste_cijfer_jaar = ($factuurnummer / 100000) % 10;
    if ($bron_boekhouding == 'AANSLUIT' || $bron_boekhouding == 'U_VERZEK') {
      $ogm_code = 43;
    }
    else if ($bron_boekhouding == 'DP') {
      $ogm_code = 45;
    }
    else {
      // assume 'CURSUS'.
      $ogm_code = 47;
    }
    $controlegetal = (int)(($laatste_cijfer_jaar . $ogm_code . $factuurnummer) % 97);
    if($controlegetal == 0){
      $controlegetal = 97;
    }
    if($controlegetal < 10){
      $controlegetal = '0' . $controlegetal;
    }
    $factuurnummerdeel1 = substr($factuurnummer, 0, -3);
    $factuurnummerdeel2 = substr($factuurnummer, -3);
    if ($human_readable):
      return '+++' . $laatste_cijfer_jaar . $ogm_code . '/'
      . $factuurnummerdeel1 . '/' .
      $factuurnummerdeel2 . $controlegetal . '+++';
    else:
      return $laatste_cijfer_jaar . $ogm_code
      . $factuurnummerdeel1 . $factuurnummerdeel2 . $controlegetal;
    endif;
  }
}