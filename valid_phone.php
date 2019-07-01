<?php
$phoneNumber = '729836307';

if(!empty($phoneNumber)) // phone number is not empty
{
    if(preg_match('/^\d{9}$/',$phoneNumber)) // phone number is valid
    {
      $phoneNumber = '0' . $phoneNumber;
echo 'valid';
      // your other code here
    }
    else // phone number is not valid
    {
      echo 'Phone number invalid !';
    }
}
else // phone number is empty
{
  echo 'You must provid a phone number !';
}
