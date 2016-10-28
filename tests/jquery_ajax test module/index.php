<?php
/**
 * @author Michael Kliewe's PHPGangsta_GoogleAuthenticator with
 * @link http://www.phpgangsta.de/
 * @author Sean Zeng's jquery_ajax test module
 * @link https://seanzeng.com/
 * Changes to GoogleAuthenticator.php:
 * - Renamed to googleauth.php, all lowercase, thus don't have to deal with cases on "case sensitive apache server".
 * - For getQRCodeGoogleUrl() script did not encode '|' thus wrote '%7C'.
 * - Created index.php to beautify and showcase this backend implementation.
 *
 * @package Files required: index.php, googleauth.php, googleauthtest.css, style.css, googlelogo_color_272x92dp.png
 * Features:
 * - CSS formatting
 * - 30sec Live Code update, start synced with server update (0-29sec) (JS timeInterval(30sec));
 * - PHP GET OR POST secret (validates secret, else stops live update)
 * - Live update of QR Code and other parameters without page refresh
 * - Interval keeps counting 30sec in background, should invalid secret be checked
 *
 * @todo Code tested to be fine for testing and public showcase. Need 2nd Opinion
 * @todo Perhaps implement $_SESSION['ip'] for DDOS purposes
 *
 * @internal If you rename, YOU MUST all instances of index.php -> 'name'.php
 *
 */

/**
 * @copyright 2012 Michael Kliewe
 * Copyright (c) 2012, Michael Kliewe All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once 'googleauth.php';

/**
 * If ajax posts back with Secret, this section takes over
 * @param string secret - 16 length, ASCII Letters and Num only
 *
 * @return echo $oneCode         - Success
 * @return echo "secret invalid" - Fail
 * SCRIPT STOPS with return
 */
$ga = new PHPGangsta_GoogleAuthenticator();
  if (!empty($_POST['secret'])) {
    if (preg_match("/^\w{16}$/", $_POST['secret'])) {
      $oneCode = $ga->getCode($_POST['secret']);
      echo $oneCode;
      return;
    } else {
      echo "secret invalid";
      return;
    }
  }

  /**
   * This file can be included in other php functions.
   * @example include_once('index.php');
   * I prefer renaming this file to googleauthtest.php
   * and including it in my website
   * If you rename, YOU MUST all instances of index.php -> 'name'.php
   *
   * Function checks if headers are needed, else skips
   * straight to echo <section>
   * @return defined 'TEST_SUITE' = __FILE__ - so that footer and </body></html> can be inserted below
   */
  if (count(get_included_files()) == 2) {
    define ('TEST_SUITE', __FILE__);
    include_once 'head.php';

    echo "<body><div class='content'>";
    echo "<h1>Time Based Authenticator <small style='color:red;'>RFC6238</small></h1>";
  }

  echo "<link id='googleAuthCSS' rel='stylesheet' type='text/css' href='googleauthtest.css'/>";
  echo "<section>";

  /**
   * Accepts php GET rather than generate own secret
   * @example index.php?secret=A567890123HNCDW2
   * @param string secret - 16 length, ASCII Letters and Num only
   */
  if (!empty($_GET['secret']) && preg_match("/^\w{16}$/", $_GET['secret'])) {
    $secret = $_GET['secret'];
    //echo "<h2 id='time-based-auth-title'>Tester Set Secret is: ".$secret."</h2>";
  } else {
    $secret = $ga->createSecret();
    //echo "<h2 id='time-based-auth-title'>Tester Generated Secret is: ".$secret."</h2>";
  }

  echo "<h2>Time Based Authenticator <small style='color:red;'>RFC6238</small></h2>";
  $qrCodeUrl = $ga->getQRCodeGoogleUrl('SomethingZ.com', $secret);
?>

<div class='center'>
  <form id='time-based-auth' action='index.php' method="post" name="authTest">
    <label><h3><img class='logo' src='images/googlelogo_color_272x92dp.png' alt=''> Authenticator</h3></label>
    <a id='time-based-auth-link' href='<?php echo $qrCodeUrl; ?>'>QR.<?php echo $secret;?></a>
    <img id='time-based-auth-img' src='<?php echo $qrCodeUrl; ?>' alt=''>
    <label> Enter Secret to Generate </label>
    <input type='text' name='secret' autocomplete='off' value="<?php echo $secret;?>" maxlength='16'>
    <input type="submit" class="button" name="time-based-auth-submit" value="Check">
    <?php
      /**
       * Sets data in the form, including the current tick of server's 30 sec counter
       *
       * @param string secret - 16 length, ASCII Letters and Num only
       *
       * @return string $oneCode - secret's answer
       * @return string $timeleft - server 30s time counting downwards:  30 - floor(time() % 30);
       */
      $oneCode = $ga->getCode($secret);
      echo "<label style='font-size:15px'>CODE: ";
      echo "<span id='time-based-auth-code'>'$oneCode'</span> ";

      $checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
      if ($checkResult) {
          echo "<span id='time-based-auth-result' style='color:lightgreen;'>VERIFIED! </span>";
      } else {
          echo "<span id='time-based-auth-result' style='color:red;'>FAILED </span>";
      }
      $timeLeft = 30 - floor(time() % 30);
      echo "<span id='time-based-auth-countdown'>".$timeLeft."<span>";
      echo "</label>";
      ?>
      <label> (PHP/JS serverside Module) </label>
  </form>
</div>

<script>
  // Store secret value for refreshes, thus user can still type freely
  var secretVal = $('#time-based-auth').find('input[name="secret"]').val();
  var timer1 = new Timer(30);

  // Start timer on document ready
  $(document).ready(function () {
    timer1.start();
  })

  // Form submit rewrites secretVal, and calls Ajax post
  $('#time-based-auth').submit(function() {
    secretVal = $('#time-based-auth').find('input[name="secret"]').val();
    postTimeBasedAuth(secretVal);
    return false;
  });

  // jQuery Ajax POST and receive data
  function postTimeBasedAuth(secretVal) {
    // Check not empty (php will return entire webpage otherwise)
    if (secretVal.length == 0) {
      $('#time-based-auth-result').text('FAILED ');
      $('#time-based-auth-result').css('color', 'red');
    } else {
      $.post("index.php", {
        secret: secretVal
        }, function(data, status){
          // return data to form
          $('#time-based-auth-code').text("'"+data+"'");
          // check if php was successful
          if (status && data != "secret invalid") {
            $('#time-based-auth-result').text('VERIFIED! ');
            $('#time-based-auth-result').css('color', 'lightgreen');
            var qrUrl = getQRCodeGoogleUrl(secretVal);
            $('#time-based-auth-img').attr('src',qrUrl);
            $('#time-based-auth-link').attr('href',qrUrl);
            $('#time-based-auth-link').text('QR.'+secretVal)
            //$('#time-based-auth-title').text('Tester Set Secret is: ' + secretVal);
          } else {
            $('#time-based-auth-result').text('FAILED ');
            $('#time-based-auth-result').css('color', 'red');
          }
      });
    }
    return;
  }

  // Ported to JS from @author Michael Kliewe PHPGangsta_GoogleAuthenticator
  // Creates a link to google generator for QR code
  function getQRCodeGoogleUrl(secret, title = 'SomethingZ.com') {
    var width = '200';
    var height = '200';
    var encodingLevel = 'M';
    var urlencoded='otpauth%3A%2F%2Ftotp%2F'+title+'%3Fsecret%3D'+secret;
    return 'https://chart.googleapis.com/chart?chs='+width+'x'+height+'&chld='+encodingLevel+'%7C0&cht=qr&chl='+urlencoded+'';
  }

  // 'Class' Function will replay one a second,
  // Posts to server every 30 sec
  //
  // Synced via the original value from server 30s count:
  // countDown = parseInt($('#time-based-auth-countdown').text());
  function Timer(max) {
    var timerInterval;
    var running = false;
    var countDown = 30;
    this.start = function() {
      if (!running) {
        // Synced via the original value from server 30s count:
        countDown = parseInt($('#time-based-auth-countdown').text());
        $('#time-based-auth-countdown').css('color','');

        timerInterval = setInterval(function() {
                      // Check if form shows FAILED secret check, if so, hides countDown, but keeps counting
                      noOutput = ($('#time-based-auth-result').text() == 'FAILED ');

                      if (countDown <= 0) {
                        countDown = max;
                        if (!noOutput){
                          postTimeBasedAuth(secretVal)
                        }
                      }
                      countDown--;
                      // Update time on form
                      if(!noOutput){
                        $('#time-based-auth-countdown').text(countDown);
                        $('#time-based-auth-countdown').css('color','');
                      } else {
                        $('#time-based-auth-countdown').css('color','red');
                      }
                    } , 1000);
        running = true;
      }
      return;
    };
    // timer can be stopped by typing timer1.stop() in google chrome console
    this.stop = function() {
      $('#time-based-auth-countdown').css('color','red');
      clearInterval(timerInterval);
      running = false;
      return 'stopped';
    };
  }
</script>

<?php
  /**
    * Close things off in html.
    * if @param 'TEST_SUITE' - defined('TEST_SUITE') && TEST_SUITE==__FILE__
    * then insert footer and </body></html>
    */
  echo "</section>";

  if (defined('TEST_SUITE') && TEST_SUITE==__FILE__) {
    echo "</div>";
    include_once 'footer.php';
    echo "</body></html>";
  }
?>
