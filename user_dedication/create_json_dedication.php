<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


//INCLUDES 
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');


defined('MOODLE_INTERNAL') || die();


redirect_if_major_upgrade_required();

// Se necesita de una cuenta para acceder
require_login();

// Almacenamos la respuesta que recibamos del post enviado en el $script1 de "block_calendar_parent"
$response = file_get_contents('php://input');
//Pasamos el JSON a array de php mediante json_decode
$response = json_decode($response);
  
//Creamos la cabecera y reenviamos el contenido de get_json() pasando la respuesta
header('Content-Type: application/json'); //Content-Type: application/json

// Diferenciamos la respuesta y almacenamos las diferentes arrays recibidas en la variable $respuesta
if($response->type == "day")
    {
    $respuesta = get_user_dedication_day($response->userId, $response->startDate, $response->finishDate);
    error_log("el 1 tipo es: " . $response->type);
    }
else if ($response->type == "week")
    {
    $respuesta = get_user_dedication_week($response->userId, $response->startDate, $response->finishDate);
    error_log("el 2 tipo es: " . $response->type);
    }
else if ($response->type == "month")
    {
    $respuesta = get_user_dedication_month($response->userId, $response->startDate, $response->finishDate, $response->year, $response->month);
    error_log("el 3 tipo es: " . $response->type);
    }

// Enviamos la array respuesta con el tiempo dedicado del alumno y los parametros que habiamos recibido en response    
echo json_encode(get_json($response, $respuesta));

// *********************************************************
// Función para crear el JSON de respuesta con la información
// *********************************************************
function get_json($response, $dedications)
    {
// Creamos la nueva array donde almacenaremos los identificadores y nombres de los usuarios hijos/tutorados
    $nombresHijos = array();
    $identificadoresUser = array();

    $nombresHijos[0] = $response->userName;///////////////////////////////////////////////
    $identificadoresUser[0] = $response->userId;


// Empezamos a crear el JSON con el campo del nombre, el tipo e iniciamos un campo de cursos donde iniciaremos el array       
    $JSON = '{"name":"'. $response->userName .'","type":"' . $response->type .'", "cursos": [';  

    $i = 0;
// Creamos un foreach por cada identificador para sacar las dedications (todos los registros de las horas) de cada alumno    
    foreach ($dedications as $dedication)
        {
// Añadimos una coma solo si no es la primera            
        if ($i != 0)
            $JSON .= ',';
// Recogemos el nombre del curso sobre el que vamos recoger el tiempo empleado              
        $JSON .= '{"name": "' . $dedication[0] . '",';
        $JSON .= '"dedicacion": [';
        $j = 0;
// Recorremos todos los registros de dedication (todo el tiempo por cada hora de un dia o los dias de una semana/mes)        
        foreach($dedication[1] as $dedicationTime)
            {                      
            if ($j != 0)
                $JSON .= ',';
//Recogemos unicamente los valores, sin clave             
                $JSON .=  $dedicationTime . ' ';            
                $j++;
                }
            $JSON .= ']}';
            $i++;   
            }
            $JSON .= ']}';            
    
//Devolvemos el JSON
    return $JSON;    
    }  



// ******************************************************************
//Función para obtener los usuarios hijo que estan asociados al padre
// ******************************************************************
function get_child_users() 
    {
    global $CFG, $USER, $DB;

//Recuperamos los campos del usuario    
    $userfieldsapi = \core_user\fields::for_name();
    $allusernames = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
//Hacemos un select de la información de los hijos del usuario actual enlazando los role_assignments, context y user.    
    if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid, $allusernames
                                               FROM {role_assignments} ra, {context} c, {user} u
                                               WHERE ra.userid = ?
                                               AND ra.contextid = c.id
                                               AND c.instanceid = u.id
                                               AND c.contextlevel = ".CONTEXT_USER, array($USER->id))) {
    }

    return $usercontexts;
    }


// ***************************************************************************************
// Funcion un array del tiempo empleado por el usuario para cada curso y cada hora del día
// ***************************************************************************************
function get_user_dedication_day($userid, $fechaIni, $fechaFin)
    {
    global $DB;

// Creamos un array para crear una matriz en la que se almacenara el tiempo
// empleado por el alumno por cada curso y para cada hora del día      
    $dedication = [];
// Array para almacenar los cursos correspondientes al alumno    
    $courseMat = [];

//Creamos la array de cursos del alumno   
    $courses = get_child_courses($userid);

// Foreach para cada curso del alumno
    foreach ($courses as $course)
        {
// Para cada curso creamos una array dentro de dedication e intrducimos el nombre del curso 
// $course->id se corresponde a la clave           
        $dedication[$course->id][0] = $course->fullname;
// Almacenamos el identificador del curso        
        $courseMat[$course->id] = $course->id;
// Creamos un array para el curso en la posición siguiente en la que metemos los 24 campos a 0
// En estos campos almacenaremos los segundos empleados en esas horas del dia para el curso en cuestión        
        for ($i=0; $i < 24; $i++)
            $dedication[$course->id][1][$i] = 0;    
        }
// Creamos la sentencia sql en la que pasamos la información recibida en el response
    $sql = "SELECT * FROM `mdl_logstore_standard_log` WHERE `userid`= $userid AND `timecreated`>= $fechaIni AND `timecreated`<= $fechaFin ORDER BY timecreated ASC";

// Almacenamos la respuesta en $works
    $works = $DB->get_records_sql($sql);
// creamos previousCourse, previousTime y previousDate almacenando mediante la clave de works el id del curso
// y el timecreated y la date
    $previousCourse =  $works[array_keys($works)[0]]->courseid;
    $previousTime = $works[array_keys($works)[0]]->timecreated;
    $previousDate = getdate($previousTime);
    $course = -1;
    

// Hacemos un foreach por cada registro recogido
    foreach ($works as $work)
        {
// recogemos el id del curso, el timecreate y la date (para poder sacar la hora en la que corresponde el timecreated)
// que timecreated esta en formato UNIX y no sabemos a que hora de las 24 corresponde            
        $course = $work->courseid;
        $time = $work->timecreated;
        $date = getdate($time);
// usamos in_array para saber si el id del curso se encuentra en $courseMat
// lo que se busca es saber si es un curso al que el alumno se ha matriculado (para diferenciar del courseid 1 y 0 (que es para el dashboard))        
        if (in_array($course, $courseMat))
            {
// si el registro anterior coincide con el que esta a continuación (esto significa que el alumno esta generando actividad todavia en el mismo curso)                 
            if ($course == $previousCourse)
                {
// Y la hora en la que esta pasando es la misma (se sigue almacenando en un mismo campo de las 24 horas)                    
                if ($date['hours'] == $previousDate['hours'])
                    {
// Simplemento sacamos la resta del tiempo del registro con el anterior y lo almacenamos para la hora y curso dada                        
                    $dedication[$course][1][$date['hours']] += $time - $previousTime;
                    }
// Si la hora no es la misma                    
                else
                    {
// Esta linea recoge el tiempo desde el tiempo del primer registro hasta que termina la hora                           
                    $dedication[$course][1][$previousDate['hours']] += (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde la nueva hora hasta el registro y se lo almacena al anterior                    
                    $dedication[$course][1][$date['hours']] += $date['minutes'] * 60 + $date['seconds'];
                    }
                }
// Si registro siguiente y el anterior no tienen el mismo curso pero es un curso al que esta matriculado              
            else
                {
// Pero la hora es la misma                    
                if ($date['hours'] == $previousDate['hours'])
                    {
// Nos aseguramos de que el registro anterior sea un curso matriculado del alumno (un curso de verdad ni 0 ni 1)                        
                    if (in_array($previousCourse, $courseMat))
                        {
// Almacenamos la resto de tiempo entre los dos registros                            
                        $dedication[$previousCourse][1][$date['hours']] += $time - $previousTime;
                        }
                    }
// Si las horas no son las mismas para los dos registros pero tampoco son el mismo curso                   
                else
                    {
// Nos aseguramos de que el registro anterior sea un curso matriculado del alumno (un curso de verdad ni 0 ni 1)
                    if (in_array($previousCourse, $courseMat))
                        {
// Esta linea recoge el tiempo desde el tiempo del primer registro hasta que termina la hora                                                        
                        $dedication[$previousCourse][1][$previousDate['hours']] += (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde la nueva hora hasta el registro y se lo almacena al anterior                          
                        $dedication[$previousCourse][1][$date['hours']] += $date['minutes'] * 60 + $date['seconds'];                                               
                        }
                    }
                }
            }
// Si el curso a continuación no es uno al que el alumno esta matriculado (es 0 o 1)            
        else
            {
// Si las hora es la misma                
            if ($date['hours'] == $previousDate['hours'])
                {
// Si el curso anterior es uno al que esta matriculado                    
                if (in_array($previousCourse, $courseMat))
                    {
// Almacenamos el resto de tiempo de ambos registros                        
                    $dedication[$previousCourse][1][$date['hours']] += $time - $previousTime;
                    }
                }
// Si la hora no es la misma                
            else
                {
// Si el curso anterior es uno al que esta matriculado                      
                if (in_array($previousCourse, $courseMat) && $work->action != 'loggedin')
                    {
// Esta linea recoge el tiempo desde el tiempo del primer registro hasta que termina la hora                          
                    $dedication[$previousCourse][1][$previousDate['hours']] += (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde la nueva hora hasta el registro y se lo almacena al anterior                                              
                    $dedication[$previousCourse][1][$date['hours']] += $date['minutes'] * 60 + $date['seconds'];                     
                    }
                }    
            }
// Por ultimo ponemos el curso anterior, tiempo y fecha con el nuevo registro para avanzar en los registros
        $previousCourse = $course;
        $previousTime = $time;
        $previousDate = $date;
        }
// Devolvemos el array entero de cursos con tiempo empleado por cada curso por cada hora
    return $dedication;
    }



// ***************************************************************************************
// Funcion un array del tiempo empleado por el usuario para cada curso y cada hora del día
// ***************************************************************************************
function get_user_dedication_week($userid, $fechaIni, $fechaFin)
    {
    global $DB;

// Creamos un array para crear una matriz en la que se almacenara el tiempo
// empleado por el alumno por cada curso y para cada dia de la semana      
    $dedication = [];
// Array para almacenar los cursos correspondientes al alumno    
    $courseMat = [];

//Creamos la array de cursos del alumno   
    $courses = get_child_courses($userid);

// Foreach para cada curso del alumno
    foreach ($courses as $course)
        {
// Para cada curso creamos una array dentro de dedication e intrducimos el nombre del curso 
// $course->id se corresponde a la clave           
        $dedication[$course->id][0] = $course->fullname;
// Almacenamos el identificador del curso        
        $courseMat[$course->id] = $course->id;
// Creamos un array para el curso en la posición siguiente en la que metemos los 7 campos a 0
// En estos campos almacenaremos los segundos empleados en esos dias para el curso en cuestión        
        for ($i=0; $i < 7; $i++)
            $dedication[$course->id][1][$i] = 0; 
        }
// Creamos la sentencia sql en la que pasamos la información recibida en el response
    $sql = "SELECT * FROM `mdl_logstore_standard_log` WHERE `userid`= $userid AND `timecreated`>= $fechaIni AND `timecreated`<= $fechaFin ORDER BY timecreated ASC";

// Almacenamos la respuesta en $works
    $works = $DB->get_records_sql($sql);
// creamos previousCourse, previousTime y previousDate almacenando mediante la clave de works el id del curso
// y el timecreated y la date
    $previousCourse =  $works[array_keys($works)[0]]->courseid;
    $previousTime = $works[array_keys($works)[0]]->timecreated;
    $previousDate = getdate($previousTime);
    $course = -1;

// Hacemos un foreach por cada registro recogido
    foreach ($works as $work)
        {
// Recogemos el id del curso, el timecreated y el date (para poder saber el dia de la semana a la que corresponde el timecreated)
// esto porque timecreated esta en formato UNIX y no sabemos a que dia de los 7 corresponde            
        $course = $work->courseid;
        $time = $work->timecreated;
        $date = getdate($time);
// Usamos in_array para saber si el id del curso se encuentra en $courseMat
// lo que se busca es saber si es un curso al que el alumno se ha matriculado (para diferenciar del courseid 1 y 0 (que es para el dashboard))        
        if (in_array($course, $courseMat))
            {
// Si el registro anterior coincide con el que esta a continuación (esto significa que el alumno esta generando actividad todavia en el mismo curso)                 
            if ($course == $previousCourse)
                {
// Y el dia en la que esta pasando es el mismo (se sigue almacenando en un mismo campo del día)                    
                if ($date['wday'] == $previousDate['wday'])
                    {
// Simplemente sacamos la resta del tiempo del registro con el anterior y lo almacenamos para el dia y curso dada
// Es necesario restarle uno porque 'wday' no empieza desde cero como nuestra array, restando 1 conseguimos que el recuento de la array sea en orden semanal                       
                    $dedication[$course][1][$date['wday'] - 1] += $time - $previousTime;                    
                    }
// Si el día no es el mismo                    
                else
                    {
// Esta linea recoge el tiempo que queda desde el primer registro hasta que termina el día                           
                    $dedication[$course][1][$previousDate['wday'] - 1] += (23 - $previousDate['hours']) * 3600 + (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde el nuevo día hasta el registro y se lo almacena al anterior                    
                    $dedication[$course][1][$date['wday'] - 1] += $date['hours'] * 3600 +  $date['minutes'] * 60 + $date['seconds'];
                    }
                }
// Si registro siguiente y el anterior no tienen el mismo curso pero es un curso al que esta matriculado              
            else
                {
// Pero el día es el mismo                    
                if ($date['wday'] == $previousDate['wday'])
                    {
// Nos aseguramos de que el registro anterior sea un curso matriculado del alumno (un curso de verdad ni 0 ni 1)                        
                    if (in_array($previousCourse, $courseMat))
                        {
// Almacenamos la resto de tiempo entre los dos registros                            
                        $dedication[$previousCourse][1][$date['wday'] - 1] += $time - $previousTime;
                        }
                    }
// Si los días no son los mismos para los dos registros pero tampoco son el mismo curso                   
                else
                    {
// Nos aseguramos de que el registro anterior sea un curso matriculado del alumno (un curso de verdad ni 0 ni 1)
                    if (in_array($previousCourse, $courseMat))
                        {
// Esta linea recoge el tiempo que queda desde el primer registro hasta que termina el día                            
                        $dedication[$previousCourse][1][$previousDate['wday'] - 1] += (23 - $previousDate['hours']) * 3600 + (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde la nuevo día hasta el registro y se lo almacena al anterior                    
                        $dedication[$previousCourse][1][$date['wday'] - 1] += $date['hours'] * 3600 +  $date['minutes'] * 60 + $date['seconds'];                                            
                        }
                    }
                }
            }
// Si el curso a continuación no es uno al que el alumno esta matriculado (es 0 o 1)            
        else
            {
// Si el día es el mismo                
            if ($date['wday'] == $previousDate['wday'])
                {
// Si el curso anterior es uno al que esta matriculado                    
                if (in_array($previousCourse, $courseMat))
                    {
// Almacenamos el resto de tiempo de ambos registros                        
                    $dedication[$previousCourse][1][$date['wday'] - 1] += $time - $previousTime;
                    }
                }
// Si el día no es el mismo                
            else
                {
// Si el curso anterior es uno al que esta matriculado                      
                if (in_array($previousCourse, $courseMat) && $work->action != 'loggedin')
                    {
// Esta linea recoge el tiempo que queda desde el primer registro hasta que termina el día                             
                    $dedication[$previousCourse][1][$previousDate['wday'] - 1] += (23 - $previousDate['hours']) * 3600 + (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde el nuevo día hasta el registro y se lo almacena al anterior                     
                    $dedication[$previousCourse][1][$date['wday'] - 1] += $date['hours'] * 3600 +  $date['minutes'] * 60 + $date['seconds'];                     
                    }
                }    
            }
// Por ultimo ponemos el curso anterior, tiempo y fecha con el nuevo registro para avanzar en los registros
        $previousCourse = $course;
        $previousTime = $time;
        $previousDate = $date;
        }
// Devolvemos el array entero de cursos con tiempo empleado por cada curso por cada día
    return $dedication;
    }



// ***************************************************************************************
// Funcion un array del tiempo empleado por el usuario para cada curso y cada hora del día
// ***************************************************************************************
function get_user_dedication_month($userid, $fechaIni, $fechaFin, $year, $month)
    {
    global $DB;

// Creamos un array para crear una matriz en la que se almacenara el tiempo
// empleado por el alumno por cada curso y para día del mes      
    $dedication = [];
// Array para almacenar los cursos correspondientes al alumno    
    $courseMat = [];

//Creamos la array de cursos del alumno   
    $courses = get_child_courses($userid);

// Mediante la siguiente función recogemos los dias que tiene el mes para el año y mes recogidos (usamos el calendario gregoriano)
    $daysMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Foreach para por cada del alumno
    foreach ($courses as $course)
        {
// Para cada curso creamos una array dentro de dedication e intrducimos el nombre del curso 
// $course->id se corresponde a la clave           
        $dedication[$course->id][0] = $course->fullname;
// Almacenamos el identificador del curso        
        $courseMat[$course->id] = $course->id;
// Creamos un array para el curso en la posición siguiente en la que metemos los dias correspondientes al mes
// en estos campos almacenaremos los segundos empleados ese dia para el curso en cuestión    
        for ($i=0; $i < $daysMonth; $i++)
            $dedication[$course->id][1][$i] = 0;
        }

// Creamos la sentencia sql en la que pasamos la información recibida en el response
    $sql = "SELECT * FROM `mdl_logstore_standard_log` WHERE `userid`= $userid AND `timecreated`>= $fechaIni AND `timecreated`<= $fechaFin ORDER BY timecreated ASC";

// Almacenamos la respuesta en $works
    $works = $DB->get_records_sql($sql);
// Creamos previousCourse, previousTime y previousDate almacenando mediante la clave de works el id del curso
// y el timecreated y la date
    $previousCourse =  $works[array_keys($works)[0]]->courseid;
    $previousTime = $works[array_keys($works)[0]]->timecreated;
    $previousDate = getdate($previousTime);
    $course = -1;

// Hacemos un foreach por cada registro recogido
    foreach ($works as $work)
        {
// Recogemos el id del curso, el timecreated y el date (para poder saber el dia del mes a la que corresponde el timecreated)
// que timecreated esta en formato UNIX y no sabemos a día del mes corresponde            
        $course = $work->courseid;
        $time = $work->timecreated;
        $date = getdate($time);
// Usamos in_array para saber si el id del curso se encuentra en $courseMat
// lo que se busca es saber si es un curso al que el alumno se ha matriculado (para diferenciar del courseid 1 y 0 (que es para el dashboard))        
        if (in_array($course, $courseMat))
            {
// si el registro anterior coincide con el que esta a continuación (esto significa que el alumno esta generando actividad todavia en el mismo curso)                 
            if ($course == $previousCourse)
                {
// Y el dia del mes de ambos registros es el mismo (se sigue almacenando en un mismo campo del día)                    
                if ($date['mday'] == $previousDate['mday'])
                    {
// Simplemente sacamos la resta del tiempo del registro con el anterior y lo almacenamos para el dia y curso dado                     
                    $dedication[$course][1][$date['mday'] - 1] += $time - $previousTime;                    
                    }
// Si el dia no es el mismo                    
                else
                    {
                        
// Esta linea recoge el tiempo que queda desde el primer registro hasta que termina el día                          
                    $dedication[$course][1][$previousDate['mday'] - 1] += (23 - $previousDate['hours']) * 3600 + (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde el nuevo día hasta el registro y se lo almacena al anterior                    
                    $dedication[$course][1][$date['mday'] - 1] += $date['hours'] * 3600 +  $date['minutes'] * 60 + $date['seconds'];
                    }
                }
// Si registro siguiente y el anterior no tienen el mismo curso pero es un curso al que esta matriculado              
            else
                {
// Pero el día no es el mismo                    
                if ($date['mday'] == $previousDate['mday'])
                    {
// Nos aseguramos de que el registro anterior sea un curso matriculado del alumno (un curso de verdad ni 0 ni 1)                        
                    if (in_array($previousCourse, $courseMat))
                        {
// Almacenamos la resto de tiempo entre los dos registros                            
                        $dedication[$previousCourse][1][$date['mday'] - 1] += $time - $previousTime;
                        }
                    }
// Si los días no son los mismos para los dos registros pero tampoco son el mismo curso                   
                else
                    {
// Nos aseguramos de que el registro anterior sea un curso matriculado del alumno (un curso de verdad ni 0 ni 1)
                    if (in_array($previousCourse, $courseMat)) 
                        {
// Esta linea recoge el tiempo que queda desde el primer registro hasta que termina el día                         
                    $dedication[$previousCourse][1][$previousDate['mday'] - 1] += (23 - $previousDate['hours']) * 3600 + (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde el nuevo día hasta el registro y se lo almacena al anterior                     
                    $dedication[$previousCourse][1][$date['mday'] - 1] += $date['hours'] * 3600 +  $date['minutes'] * 60 + $date['seconds'];                                            
                        }
                    }
                }
            }
// Si el curso a continuación no es uno al que el alumno esta matriculado (es 0 o 1)            
        else
            {
// Si los días no son los mismos                
            if ($date['mday'] == $previousDate['mday'])
                {
// Si el curso anterior es uno al que esta matriculado                    
                if (in_array($previousCourse, $courseMat))
                    {
// Almacenamos el resto de tiempo de ambos registros                        
                    $dedication[$previousCourse][1][$date['mday'] - 1] += $time - $previousTime;
                    }
                }
// Si el día no es el mismo                
            else
                {
// Si el curso anterior es uno al que esta matriculado                      
                if (in_array($previousCourse, $courseMat) && $work->action != 'loggedin')
                    {
// Esta linea recoge el tiempo que queda desde el primer registro hasta que termina el día                         
                    $dedication[$previousCourse][1][$previousDate['mday'] - 1] +=  (23 - $previousDate['hours']) * 3600 + (59 - $previousDate['minutes']) * 60 + 60 - $previousDate['seconds'];
// Esta linea recoge desde el nuevo día hasta el registro y se lo almacena al anterior                     
                    $dedication[$previousCourse][1][$date['mday'] - 1] += $date['hours'] * 3600 +  $date['minutes'] * 60 + $date['seconds'];                     
                    }
                }    
            }
// Por ultimo ponemos el curso anterior, tiempo y fecha con el nuevo registro para avanzar en los registros
        $previousCourse = $course;
        $previousTime = $time;
        $previousDate = $date;

    }
// Devolvemos el array entero de cursos con tiempo empleado por cada curso por cada día del mes
    return $dedication;
    }

    

// *****************************************************************************
//Función para extraer los cursos de un usuario por el identificador del usuario
// *****************************************************************************
function get_child_courses($id)
    {
    global $DB;

    //Realizamos un select en el que extraemos todos los cursos asociados al identificador como parametro (del hijo)
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, ue.timestart AS startdate, ue.timeend AS enddate FROM mdl_course c 
    JOIN mdl_user_enrolments ue
    JOIN mdl_user u ON u.id = ue.userid 
    JOIN mdl_enrol e ON e.id = ue.enrolid  
    WHERE u.id = $id 
    AND c.id = e.courseid";

    //get_records_sql nos devuelve el resultado de la sentencia
    return $DB->get_records_sql($sql);
    }
