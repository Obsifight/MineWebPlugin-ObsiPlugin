<?php
class FactionsRankingController extends ObsiAppController {

  public function index() {
    $this->set('title_for_layout', 'Classement des factions');

    $pointsCalcul = array(
      /*array(
        'title' => 'Tués',
        'td' => array(
          'En dessous de <b>1 000</b>' => '10 points/tués',
          'De <b>1 000</b> à <b>2 000</b>' => '12 points/tués',
          'De <b>2 000</b> à <b>3 000</b>' => '15 points/tués',
          'De <b>3 000</b> à <b>4 000</b>' => '17 points/tués',
          'De <b>4 000</b> à <b>5 000</b>' => '19 points/tués',
          'De <b>5 000</b> à <b>6 000</b>' => '25 points/tués',
          'Au dessus de <b>6 000</b>' => '27 points/tués'
        )
      ),
      array(
        'title' => 'Morts',
        'td' => array(
          'En dessous de <b>1 000</b>' => '-12 points/morts',
          'De <b>1 000</b> à <b>2 000</b>' => '-14 points/morts',
          'De <b>2 000</b> à <b>3 000</b>' => '-17 points/morts',
          'De <b>3 000</b> à <b>4 000</b>' => '-19 points/morts',
          'De <b>4 000</b> à <b>5 000</b>' => '-21 points/morts',
          'De <b>5 000</b> à <b>6 000</b>' => '-27 points/morts',
          'Au dessus de <b>6 000</b>' => '-29 points/morts'
        )
      )*/
    );

    $this->set(compact('pointsCalcul'));
  }

}
