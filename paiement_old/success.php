<?php
require_once( 'trads.php' );

session_start();
if (isset($_SESSION['langue'])) {
    $langue = $_SESSION['langue'];
}else{
    $langue = "fr";
}
unset($_SESSION['langue']);
?>

<!doctype html>
<html lang="en">
    <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <style type="text/css">
.top {
	border-bottom: 1px solid #ddd;
}
.top img {
	width: 200px;
	margin: 15px auto;
	display: block;
}
</style>
    <body>
<div class="container-fluid">
      <div class="row">
    <div class="col-lg-12">
          <div class="top"> <img src="https://www.rdvasie.com/wp-content/uploads/2018/11/rdv-asie.png" alt="Rendez-vous avec l'Asie Logo">
        </p>
      </div>
        </div>
    <div class="col-lg-12" style="text-align: center;"> <i class="far fa-check-circle" style="font-size: 72px;color:#27d04d; margin: 30px 0;"></i>
          <h1> <?php echo $accept[$langue]; ?> !</h1>
          <h2><?php echo $conf[$langue]; ?>.</h2>
          <h3 style="margin-top: 30px"><?php echo $question[$langue]; ?> ?</h3>
          <p class="texte-footer"> <?php echo $appel[$langue]; ?> 02.72.64.40.34<br>
              <?php echo $mail[$langue]; ?> : contact@rdvasie.com</p>
         <a style="display: block; color: #e25900" href="https://www.rdvasie.com"> <i class="fas fa-home"></i> wwww.rdvasie.com</a> 
        </div>
  </div>
    </div>

<!-- Optional JavaScript --> 
<!-- jQuery first, then Popper.js, then Bootstrap JS --> 
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script> 
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>
