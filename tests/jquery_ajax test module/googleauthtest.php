<?php

require_once 'googleauth.php';

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

  // Check if file is run by it's self
  if (count(get_included_files()) == 2) {
    define ('TEST_SUITE', __FILE__);
    include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

    echo "<body><div class='content'>";
    echo "<h1>Time Based Authenticator <small style='color:red;'>RFC6238</small></h1>";
  }

  echo "<link id='googleAuthCSS' rel='stylesheet' type='text/css' href='/login/css/googleauthtest.css'/>";
  echo "<section>";

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
  <form id='time-based-auth' action='/login/loginmodules/googleauthtest.php' method="post" name="authTest">
    <label><h3><img class='logo' src='/login/loginmodules/googlelogo_color_272x92dp.png' alt=''> Authenticator</h3></label>
    <a id='time-based-auth-link' href='<?php echo $qrCodeUrl; ?>'>QR.<?php echo $secret;?></a>
    <img id='time-based-auth-img' src='<?php echo $qrCodeUrl; ?>' alt=''>
    <label> Enter Secret to Generate </label>
    <input type='text' name='secret' autocomplete='off' value="<?php echo $secret;?>" maxlength='16'>
    <input type="submit" class="button" name="time-based-auth-submit" value="Check">
    <?php
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
  var secretVal = $('#time-based-auth').find('input[name="secret"]').val();
  var timer1 = new Timer(30);

  $(document).ready(function () {
    timer1.start();
  })

  $('#time-based-auth').submit(function() {
    secretVal = $('#time-based-auth').find('input[name="secret"]').val();
    postTimeBasedAuth(secretVal);
    return false;
  });

  // jQuery Ajax POST and receive data
  function postTimeBasedAuth(secretVal) {
    if (secretVal.length == 0) {
      $('#time-based-auth-result').text('FAILED ');
      $('#time-based-auth-result').css('color', 'red');
    } else {
      $.post("/login/loginmodules/googleauthtest.php", {
        secret: secretVal
        }, function(data, status){
          $('#time-based-auth-code').text("'"+data+"'");
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

  // Ported to JS from @author Michael Kliewe PHPGangsta_GoogleAuthenticator, Sean Z
  function getQRCodeGoogleUrl(secret, title = 'SomethingZ.com') {
    var width = '200';
    var height = '200';
    var encodingLevel = 'M';
    var urlencoded='otpauth%3A%2F%2Ftotp%2F'+title+'%3Fsecret%3D'+secret;
    return 'https://chart.googleapis.com/chart?chs='+width+'x'+height+'&chld='+encodingLevel+'%7C0&cht=qr&chl='+urlencoded+'';
  }

  function Timer(max) {
    var timerInterval;
    var running = false;
    var countDown = 5;
    this.start = function() {
      if (!running) {
        countDown = parseInt($('#time-based-auth-countdown').text());
        $('#time-based-auth-countdown').css('color','');

        timerInterval = setInterval(function() {

                      noOutput = ($('#time-based-auth-result').text() == 'FAILED ');

                      if (countDown <= 0) {
                        countDown = max;
                        if (!noOutput){
                          postTimeBasedAuth(secretVal)
                        }
                      }
                      countDown--;
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
    this.stop = function() {
      $('#time-based-auth-countdown').css('color','red');
      clearInterval(timerInterval);
      running = false;
      return 'stopped';
    };
  }
</script>

<?php

  echo "</section>";

  if (defined('TEST_SUITE') && TEST_SUITE==__FILE__) {
    echo "</div>";
    include_once $_SERVER['DOCUMENT_ROOT'].'/footer.php';
    echo "</body></html>";
  }
?>
