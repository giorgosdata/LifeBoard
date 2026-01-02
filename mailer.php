<?php
function send_app_mail(string $to, string $subject, string $html): bool {
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: LifeBoard <no-reply@lifeboard>\r\n";

  return @mail($to, $subject, $html, $headers);
}
