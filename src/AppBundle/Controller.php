<?php

namespace App\AppBundle;
use App\AppBundle\Models\Pictures;
use App\AppBundle\Models\Notifications;
use App\AppBundle\Models\UserLocation;
use phpDocumentor\Reflection\Location;

/**
 * @author Alexandre Hoareau <ahoareau@student.42.fr>
 */
class Controller
{
    protected $app;
    protected $view;
    protected $router;
    protected $flash;

    public function __construct($container)
    {
        $this->app = $container;
        $this->view = $container->view;
        $this->flash = $container->flash;
        $this->router = $container->router;
    }

    public function isLogged()
    {
        if (isset($_SESSION['user']) && !empty($_SESSION['user']))
        {
            $co = new IsConnected($this->app);
            if ($co->alreadyConnect($_SESSION['user']['id']))
            {
                $co->isDisconnected($_SESSION['user']['id']);
                return false;
            }
            $co->connect($_SESSION['user']['id']);
            return true;
        }
        return false;
    }

    public function getUserId()
    {
        if ($this->isLogged())
        {
            return ($_SESSION['user']['id']);
        }
        return false;
    }

    public function getNotifications()
    {
        $users = new Notifications($this->app);
        $notifications = $users->getNotification($this->getUserId());

        return $notifications;
    }

    public function getCountUnreadNotif()
    {
        $notif = new Notifications($this->app);
        $nb = $notif->getCountUnreadNotif($this->getUserId());

        return $nb;
    }

    public function hasProfilPic()
    {
        $img = new Pictures($this->app);
        return $img->getProfilPic($this->getUserId());
    }

    public function getLitteralIp()
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ] as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                // … et pour chacune de leurs valeurs…
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
// if is an IP address but not an intern (192.0.0.1) or a loopback (127.0.0.1)
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false
                        && ( ( ip2long( $ip ) & 0xff000000 ) != 0x7f000000 ) ) {
                        return ip2long($ip);
                    }
//for testing on localhost
                    else
                        return ip2long('90.91.123.174');
                }
            }
        }
        return false;
    }

    public function removeAccents($str, $charset)
    {
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        return $str;
    }
}