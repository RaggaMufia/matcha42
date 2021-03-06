<?php

namespace App\AppBundle\Models;

use App\AppBundle\Controller;
use App\AppBundle\Model;

class Users extends Model
{
    public function getHome($id = null)
    {
        $us = $this->app->db->prepare("SELECT u.name, u.lastname, u.age, u.gender, u.orientation, u.interests, u.popularity, u.is_connected, u.id AS id_user, img.url, img.is_profil, ul.city, ul.region, ul.zipCode
                    FROM users u
                    LEFT JOIN userlocation ul ON u.id = ul.id_user
                    LEFT JOIN pictures img ON img.id_user = u.id
                    WHERE u.id != ? AND img.is_profil = 1
                    ORDER BY u.popularity DESC
                    LIMIT 0,12");
        $us->execute([$id]);

        return $us->fetchAll();
    }

    public function getUserData($id)
    {
        $app = new Controller($this->app);
        $userId = $app->getUserId();
        $us = $this->app->db->prepare("SELECT u.name, u.lastname, u.age, u.resume, u.gender, u.orientation, u.is_connected, pics.url, pics.is_profil, u.interests, u.id AS id_user, ul.city, ul.region, ul.zipCode, ul.lat, ul.lon, COUNT(ui2.interest) as matchInterest, (CASE 
                              WHEN u.popularity < 0 THEN 'looser'
                              WHEN u.popularity < 100 THEN 'noob'
                              WHEN u.popularity < 500 THEN 'not bad'
                              WHEN u.popularity < 1000 THEN 'BG'
                              WHEN u.popularity < 2000 THEN 'Master of love'
                              ELSE 'god'
                              END) as grade
                    FROM users u
                    LEFT JOIN pictures pics ON pics.id_user = u.id AND pics.is_profil = 1
                    LEFT JOIN userinterests ui ON ui.id_user = u.id
                    LEFT JOIN (SELECT interest FROM userinterests WHERE id_user = $userId) ui2 ON ui2.interest = ui.interest
                    LEFT JOIN userlocation ul ON u.id = ul.id_user
                    WHERE u.id = ?
                    GROUP BY u.name, u.lastname, u.age, u.resume, u.gender, u.orientation, pics.url, u.interests, u.is_connected, u.id, ui.id_user, ul.city, ul.region, ul.zipCode, ul.lon, ul.lat
");
        $us->execute([$id]);
        $userData = $us->fetch();
        if (!empty($userData))
            return $userData;
        return [];
    }

    public function findSearch($string, $id)
    {
        $usersL = $this->app->db->prepare("SELECT u.name, u.lastname, u.age, u.resume, u.gender, u.orientation, u.popularity, u.is_connected, pics.url, pics.is_profil, u.interests, u.id AS id_user, ul.city, ul.region, ul.zipCode, ul.lat, ul.lon, COUNT(ui2.interest) as matchInterest, (CASE 
                              WHEN u.popularity < 0 THEN 'looser'
                              WHEN u.popularity < 100 THEN 'noob'
                              WHEN u.popularity < 500 THEN 'not bad'
                              WHEN u.popularity < 1000 THEN 'BG'
                              WHEN u.popularity < 2000 THEN 'Master of love'
                              ELSE 'god'
                              END) as grade
                    FROM users u
                    LEFT JOIN pictures pics ON pics.id_user = u.id AND pics.is_profil = 1
                    LEFT JOIN userinterests ui ON ui.id_user = u.id
                    LEFT JOIN (SELECT interest FROM userinterests WHERE id_user = $id) ui2 ON ui2.interest = ui.interest
                    LEFT JOIN userlocation ul ON u.id = ul.id_user
                    WHERE (u.lastname LIKE :terms OR u.name LIKE :terms OR u.gender LIKE :terms OR u.orientation LIKE :terms OR ul.city LIKE :terms OR ul.zipCode LIKE :terms) AND u.id != $id
                    GROUP BY u.name, u.lastname, u.age, u.resume, u.gender, u.orientation, u.interests, u.is_connected, u.popularity, u.id, ui.id_user, pics.id, ul.city, ul.region, ul.zipCode, ul.lon, ul.lat
                    ORDER BY matchInterest DESC , u.popularity DESC 
");
        $usersL->execute(['terms' => $string . '%']);
        $usersL = $usersL->fetchAll();

        return $usersL;

    }

    public function getUsersByOrientation($id, $orientation)
    {
        $pdo = $this->app->db->prepare("SELECT u.name, u.lastname, u.age, u.resume, u.gender, u.orientation, u.popularity,
        u.is_connected, pics.url, pics.is_profil, u.interests, u.id AS id_user, ul.city, ul.region, ul.zipCode, ul.lat, 
        ul.lon, COUNT(ui2.interest) as matchInterest, (CASE 
                              WHEN u.popularity < 0 THEN 'looser'
                              WHEN u.popularity < 100 THEN 'noob'
                              WHEN u.popularity < 500 THEN 'not bad'
                              WHEN u.popularity < 1000 THEN 'BG'
                              WHEN u.popularity < 2000 THEN 'Master of love'
                              ELSE 'god'
                              END) as grade
                    FROM users u
                    LEFT JOIN userinterests ui ON ui.id_user = u.id
                    LEFT JOIN (SELECT interest FROM userinterests WHERE id_user = $id) ui2 ON ui2.interest = ui.interest
                    LEFT JOIN userlocation ul ON ul.id_user = u.id
                    LEFT JOIN pictures pics ON pics.id_user = u.id AND pics.is_profil = 1
                    LEFT JOIN (SELECT id_user_blocked FROM usersblocked WHERE id_user = $id) ub ON ub.id_user_blocked = u.id
                    LEFT JOIN (SELECT id_user FROM usersblocked WHERE id_user_blocked = $id) ub2 ON ub2.id_user = u.id
                    WHERE ub.id_user_blocked IS NULL AND ub2.id_user IS NULL AND (u.gender LIKE (CASE '$orientation'
                                          WHEN 'homo' THEN (
                                                      CASE u.orientation
                                                      WHEN 'man' THEN 'male'
                                                      WHEN 'woman' THEN 'female'
                                                      END)
                                          WHEN 'hetero' THEN (
                                                      CASE u.orientation
                                                      WHEN 'man' THEN 'female'
                                                      WHEN 'woman' THEN 'male'
                                                      END)
                                          WHEN 'bisexual' THEN (
                                                      CASE u.orientation
                                                      WHEN 'bisexual' THEN '%'
                                                      END)
                                          END))
        AND u.id != $id
        GROUP BY u.name, u.lastname, u.age, u.resume, u.gender, u.orientation, u.interests, u.is_connected,
        u.popularity, u.id, ui.id_user, pics.id, ul.city, ul.region, ul.zipCode, ul.lon, ul.lat
        ORDER BY matchInterest DESC , u.popularity DESC");
        $pdo->execute();
        return $pdo->fetchAll();
    }

    public function getUsersByInterest($id, $string)
    {
        if (strstr($string, '#', true) === false)
            $string = '#'.$string;
        $pdo = $this->app->db->prepare("SELECT ui.id, ui.interest, ui.id_user FROM userinterests ui WHERE ui.id_user != $id AND ui.interest = '$string'");
        $pdo->execute();
        $res = $pdo->fetchAll();
        foreach ($res as $elem)
            $users[] = $this->getUserData($elem['id_user']);

        return $users;
    }


    public function updatedLogin($id, $status)
    {
        $date = date("d/m/Y H:i:s");
        $us = $this->app->db->prepare("UPDATE users SET last_seen = ?, is_connected = ? WHERE id = ?");
        $us->execute([$date, $status, $id]);
    }

    public function setSaltForget($id)
    {
        $salt = uniqid();
        $this->update($id, array('salt' => $salt));

        return $salt;
    }

    public function getLocationById($id)
    {
        $loca = $this->app->db->prepare("SELECT * FROM usersLocation WHERE id_users = ?");
        $loca->execute(array($id));
        return $loca->fetch();
    }

    public function getAllUser()
    {
        $pdo = $this->app->db->prepare("SELECT u.*, ui.url, ul.city, ul.longitude, ul.latitude FROM Users u INNER JOIN usersImage ui ON ui.id_users = u.id AND ui.isprofil = 1 INNER JOIN usersLocation ul ON ul.id_users = u.id");
        $pdo->execute();
        return $pdo->fetchAll();
    }

    public function checkPass($id, $old, $pass1, $pass2)
    {
        $user = $this->getUsers($id);

        if(hash('whirlpool', $old) != $user['password'])
            return -1;
        else if ($pass1 != $pass2)
            return -2;
        else
            $this->update($id, array('password' => hash('whirlpool', $pass1)));
        return 1;
    }

    public function changePass($id, $salt, $pass)
    {
        $user = $this->getUsers($id);
        if ($user['salt'] == $salt)
        {
            $this->update($id, array('password' => hash('whirlpool', $pass),
                'salt'   => uniqid()));
            return true;
        }
        return false;
    }

    public function setDisconnected($id)
    {
        $date = date("d/m/Y H:i:s");
        $us = $this->app->db->prepare("UPDATE users SET last_seen = ?, is_connected = ? WHERE id = ?");
        $us->execute([$date, 0, $id]);
    }

    public function checkLog($value)
    {
        $pdo = $this->app->db->prepare("SELECT * FROM users WHERE mail = ? AND password = ?");
        $pdo->execute([$value['mail'], hash('whirlpool', $value['password'])]);
        $us = $pdo->fetch();
        if (empty($us))
            return false;

        return $us;
    }

    public function getUserInterest($id)
    {
        $pdo = $this->app->db->prepare("SELECT interests FROM users u WHERE u.id = ? ");
        $pdo->execute([$id]);

        return $pdo->fetch();
    }

    public function getUser($id)
    {
        $pdo = $this->app->db->prepare("SELECT * FROM users u WHERE u.id = ?");
        $pdo->execute([$id]);

        return $pdo->fetch();
    }

    public function getUsersByDate()
    {

        $date = new DateTime('-7 days');
        $date = $date->format('d/m/Y');

        $pdo = $this->app->db->prepare("SELECT SUBSTRING(created_at, 1, 10) as date, count(SUBSTRING(created_at, 1, 10)) as cpt FROM Users WHERE created_at >= ? group by date");
        $pdo->execute(array($date));
        return $pdo->fetchAll();
    }

    public function getAllUsers()
    {
        $user = $this->app->db->prepare("SELECT u.*, url, count(r.id_users_reported) as cptRe FROM Users u LEFT JOIN usersImage ui ON u.id=ui.id_users AND ui.isprofil = 1 LEFT JOIN reported r ON r.id_users_reported = u.id AND r.id is not NULL GROUP by r.id_users_reported, IF(r.id_users_reported IS NULL, u.id, 0) ");
        $user->execute();

        return $user->fetchAll();
    }

    public function getStringInterest($id)
    {
        $pdo = $this->app->db->prepare("SELECT ui.interest FROM Users u 
										INNER JOIN users_usersInterest uui ON uui.id_users = u.id
										INNER JOIN usersInterest ui ON uui.id_interest = ui.id
										WHERE u.id = ? ");
        $pdo->execute(array($id));
        foreach ($pdo->fetchAll() as $k => $v)
            $arr[] = $v['interest'];
        if (empty($arr))
            return false;

        return implode(',', $arr);
    }

    public function deleteInfo($id)
    {


        $pdo = $this->app->db->prepare("DELETE FROM usersImage WHERE id_users = ?");
        $pdo->execute(array($id));

        $pdo = $this->app->db->prepare("DELETE FROM users_usersInterest WHERE id_users = ?");
        $pdo->execute(array($id));

        $pdo = $this->app->db->prepare("DELETE FROM usersLocation WHERE id_users = ?");
        $pdo->execute(array($id));

        $pdo = $this->app->db->prepare("DELETE FROM notification WHERE id_users = ? OR id_users_send = ?");
        $pdo->execute(array($id, $id));

        $pdo = $this->app->db->prepare("DELETE FROM likable WHERE id_users = ? OR id_users_like = ?");
        $pdo->execute(array($id, $id));

        $pdo = $this->app->db->prepare("DELETE FROM chat WHERE id_auteur = ? OR id_receiver = ?");
        $pdo->execute(array($id, $id));

        $pdo = $this->app->db->prepare("DELETE FROM history WHERE id_users = ? OR id_users_visited = ?");
        $pdo->execute(array($id, $id));

        $pdo = $this->app->db->prepare("DELETE FROM usersblocked WHERE id_users = ? OR id_users_block = ?");
        $pdo->execute(array($id, $id));

        $pdo = $this->app->db->prepare("DELETE FROM reported WHERE id_users = ? OR id_users_reported = ?");
        $pdo->execute(array($id, $id));

        $pdo = $this->app->db->prepare("DELETE FROM Users WHERE id = ?");
        $pdo->execute(array($id));

    }
    public function countUserImage($id)
    {
        $pdo = $this->app->db->prepare("SELECT pics.url FROM users u 
										INNER JOIN pictures pics ON u.id = pics.id_user
										WHERE u.id = ? ");
        $pdo->execute(array($id));

        return count($pdo->fetchAll());
    }

    public function getImages($id)
    {
        $pdo = $this->app->db->prepare("SELECT pics.id, pics.url, pics.is_profil FROM users u 
										INNER JOIN pictures pics ON u.id = pics.id_user
										WHERE u.id = ? ");
        $pdo->execute(array($id));

        return $pdo->fetchAll();
    }

    public function isNotLoca($id)
    {
        $pdo = $this->app->db->prepare("SELECT * FROM usersLocation WHERE id_users = ?");
        $pdo->execute(array($id));

        return $pdo->fetch();
    }

    public function getStatuts($id, $idUser)
    {
        if ($id == $idUser)
            return -1;
        $like = $this->app->db->prepare("SELECT *
        FROM likable 
        WHERE (id_users = ? AND id_users_like = ?) OR (id_users = ? AND id_users_like = ?)");
        $like->execute(array($idUser, $id, $id, $idUser));
        if (count($like->fetchAll()) == 2)
        {
            return 2;
        }
        $like = $this->app->db->prepare("SELECT *
        FROM likable 
        WHERE id_users = ? AND id_users_like = ?");
        $like->execute(array($id, $idUser));
        if (count($like->fetchAll()) == 1)
        {
            return 1;
        }

        return 0;

    }

    public function getImageProfil($id)
    {
        $pdo = $this->app->db->prepare("SELECT pics.url FROM users u 
										INNER JOIN pictures pics ON u.id = pics.id_user
										WHERE u.id = ? AND pics.is_profil = 1");
        $pdo->execute(array($id));

        return $pdo->fetch();
    }

    public function getCity($id)
    {
        $pdo = $this->app->db->prepare("SELECT l.city FROM Users u
                                    INNER JOIN usersLocation l ON l.id_users = u.id
                                    WHERE u.id = ?");
        $pdo->execute(array($id));

        return $pdo->fetch();
    }

    public function getSuggest($id = null, $orientation = null, $gender = null)
    {
        $users = $this->getUserData($id);
        if (empty($orientation))
            $orientation = $users['orientation'];
        if (empty($gender))
            $gender = $users['gender'];

        $pdo = $this->app->db->prepare("SELECT u.name, u.lastname, u.age, u.gender, u.orientation, u.interests, 
        u.is_connected, u.popularity, u.id AS id_user, pics.url, pics.is_profil, ul.city, ul.region, ul.zipCode, ul.lon,
        ul.lat, COUNT(ui2.interest) as matchInterest, ub.id_user_blocked, ub2.id_user as isBlocked, (CASE 
                              WHEN u.popularity < 0 THEN 'looser'
                              WHEN u.popularity < 100 THEN 'noob'
                              WHEN u.popularity < 500 THEN 'not bad'
                              WHEN u.popularity < 1000 THEN 'BG'
                              WHEN u.popularity < 2000 THEN 'Master of love'
                              ELSE 'god'
                              END) as grade
        FROM users u
        LEFT JOIN userinterests ui ON ui.id_user = u.id
        LEFT JOIN (SELECT interest FROM userinterests WHERE id_user = $id) ui2 ON ui2.interest = ui.interest
        LEFT JOIN userlocation ul ON ul.id_user = u.id
        LEFT JOIN pictures pics ON pics.id_user = u.id AND pics.is_profil = 1
        LEFT JOIN (SELECT id_user_blocked FROM usersblocked WHERE id_user = $id) ub ON ub.id_user_blocked = u.id
        LEFT JOIN (SELECT id_user FROM usersblocked WHERE id_user_blocked = $id) ub2 ON ub2.id_user = u.id
        WHERE ub.id_user_blocked IS NULL AND ub2.id_user IS NULL AND u.gender LIKE (CASE '$gender'
                              WHEN 'female' THEN (
                                CASE '$orientation'
                                WHEN 'man' THEN (
                                  CASE u.orientation
                                  WHEN 'woman' THEN 'male'
                                  WHEN 'bisexual' THEN 'male'
                                  END )
                                WHEN 'woman' THEN (
                                  CASE u.orientation
                                  WHEN 'woman' THEN 'female'
                                  WHEN 'bisexual' THEN 'female'
                                  END )
                                WHEN 'bisexual' THEN (
                                  CASE u.orientation
                                  WHEN 'woman' THEN '%%'
                                  WHEN 'bisexual' THEN '%%'
                                  END )
                                END )
                              WHEN 'male' THEN (
                                CASE '$orientation'
                                WHEN 'man' THEN (
                                  CASE u.orientation
                                  WHEN 'man' THEN 'male'
                                  WHEN 'bisexual' THEN 'male'
                                  END )
                                WHEN 'woman' THEN (
                                  CASE u.orientation
                                  WHEN 'man' THEN 'female'
                                  WHEN 'bisexual' THEN 'female'
                                  END )
                                WHEN 'bisexual' THEN (
                                  CASE u.orientation
                                  WHEN 'man' THEN '%%'
                                  WHEN 'bisexual' THEN '%%'
                                  END )
                                END )
                              WHEN 'other' THEN (
                                CASE '$orientation'
                                WHEN 'man' THEN (
                                  CASE u.orientation
                                  WHEN 'bisexual' THEN 'male'
                                  END )
                                WHEN 'woman' THEN (
                                  CASE u.orientation
                                  WHEN 'bisexual' THEN 'female'
                                  END )
                                WHEN 'bisexual' THEN (
                                  CASE u.orientation
                                  WHEN 'bisexual' THEN '%%'
                                  END )
                                END )
                              END )
        AND u.id != $id
        GROUP BY u.name, u.lastname, u.age, u.resume, u.gender, u.orientation, u.interests, u.is_connected, u.popularity, u.id, ui.id_user, pics.id, ul.city, ul.region, ul.zipCode, ul.lon, ul.lat
        ORDER BY matchInterest DESC , u.popularity DESC 
        ");

        $pdo->execute();
        return $pdo->fetchAll();
    }
}