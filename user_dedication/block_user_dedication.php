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

class block_user_dedication extends block_base 
  {

// Inicializamos el bloque.

  public function init() 
    {
    $this->title = get_string('pluginname', 'block_user_dedication');
    }

// Función para mostrar los datos en el bloque    
  public function get_content() 
    {

    if ($this->content !== null)
      return $this->content;

      
// Función de Javascript para filtrar la información sobre las entregas y cursos
    $script1 = "<script>function show_time()
      {
// Obtenemos el ID del select creado mas adelante
      sel = document.getElementById('myUserSelect');
// Recogemos los valores de los select y los almacenamos en variables      
      userId = sel.value;
      userName = sel.options[sel.selectedIndex].text;
      day = document.getElementById('myDay').value;
      week = document.getElementById('myWeek').value.trim();
      month = document.getElementById('myMonth').value;
      
//Para pasar a formato UNIX antes de enviarlo a create_canvas() ya con el formato adecuado              

      startDate = '';
      finishDate = '';
      type = '';
      mes = '';
      año = '';
      if (day !== '')
        {
        day = Date.parse(day);
        day = day.toString().substring(0, 10);
        day = parseInt(day);
        startDate = day;
        finishDate = day + 86399;
        type = 'day';
        }
      if (week.length > 0)
          {
          startDate = getDaysWeek(week);
          startDate = Date.parse(startDate);
          startDate = startDate.toString().substring(0, 10);
          startDate = parseInt(startDate);
          finishDate = startDate + 604799;
          type = 'week';

          }
      if (month.length > 0)
          {
          startDate = getDaysMonth(month);
          startDate = Date.parse(startDate);
          startDate = startDate.toString().substring(0, 10);
          startDate = parseInt(startDate);
          finishDate = getLastDaysMonth(month);
          finishDate = Date.parse(finishDate);
          finishDate = finishDate.toString().substring(0, 10);
          finishDate = parseInt(finishDate);
          type = 'month';
// Recogemos el año y el mes para enviarlo como parametros
          año = parseInt(month.substring(0, 4), 10);
          mes = parseInt(month.substring(5, 7), 10);          
          }


// Enviamos estos parametros a la pagina create_canvas.php
      var url = '$CFG->wwwroot/moodle/blocks/user_dedication/create_json_dedication.php';
      var data = {userName: userName, userId: userId, startDate: startDate, finishDate: finishDate, type: type, year: año, month: mes};

// Realizamos el envío tipo post enviando data en formato JSON
      fetch(url, {
        method: 'POST',
        body: JSON.stringify(data), // data can be `string` or {object}!      
        headers:{
          'Content-Type': 'application/json'
        }
      }).then(res => res.json()) 
      .catch(error => console.error('Error:', error))
// Si no hay errores en la respuesta, se llama al script drawDedication      
      .then(response => drawDedication(response));           
      }    
      </script>";


      $script2 = "<script>function deleteDates(actualDate) 
        {
        const inputDates = ['myDay', 'myWeek', 'myMonth'];
        inputDates.forEach((Date) => {
            if (Date !== actualDate) {
              document.getElementById(Date).value = '';
            }
          });
        }
      </script>";

// Creamos un segundo script llamado create_canvas en el que enviamos lo que se reciba de create_canvas.php para visualizar la información    
    $script3 = "<script>function create_canvas(response)
      {
// Pasamos la respuesta JSON a objeto        
      //respuesta = JSON.parse(response);
      drawDedication(response);
      //currentDiv = document.getElementById('info_time').innerHTML = response;

      
      }
      </script>";
// Script para obtener todos los dias de la semana    
    $script4 ="<script>function getDaysWeek(week)
      {
      year = parseInt(week.substring(0, 4), 10);
      week = parseInt(week.substring(6, 8), 10);
      d = new Date('1 january ' + year);
      day = d.getDay();
      if (day == 0)
        days = (2 + (week - 1) * 7);
      else if (day == 1)
          days = (1 + (week - 1) * 7);
      else
          days = (9 - day + (week - 1) * 7);
      return new Date(year, 0, days);
      }
    </script>";
// Script para devolver el primer dia del mes    
    $script5 ="<script>function getDaysMonth(month)
      {
      year = parseInt(month.substring(0, 4), 10);
      month = parseInt(month.substring(5, 7), 10);
     
      
      return new Date(year, month - 1);
      }
    </script>";
// Script para devolver el ultimo dia del mes
    $script6 ="<script>function getLastDaysMonth(month)
    {
    year = parseInt(month.substring(0, 4), 10);
    month = parseInt(month.substring(5, 7), 10);
   
    
    return new Date(year, month , 0);
    }
  </script>";
//
$script7 = "<script src='$CFG->wwwroot/moodle/blocks/user_dedication/canvasDedication.js'></script>";

// Creamos unas etiquetas de estilos para todo el HTML
    $style = "<style>
      .myDIV 
        { 
        margin-bottom: 50px;
        }
      
      #info_time 
        {
        width: 100%;
        text-align: center;
        justify-content: center; 
        }
      .divSelect 
        {
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: space-evenly;
        }
      .divSelect select
        {
        margin-right: 10px; // Agrega espacio de 10px a la derecha del input
        margin-left: 10px;
        padding: 5px 10px;
        }
// Estilos para los inputs de las fechas        
      .divSelect input[type='date'] 
        {
        flex-grow: 1; // Permite que los inputs de tipo date se expandan para llenar el espacio disponible
        flex-shrink: 0;// Evita que los inputs de tipo date se reduzcan más de lo normal
        margin-right: 20px; // Agrega espacio de 10px a la derecha del input
        margin-left: 20px;
        }
      .divSelect button
        {
        padding: 5px 10px;
        }
      .divCanvas
      {
        display: flex;
        align-items: center;
        justify-content: center;
      }         
                </style>";

// Creamos la estructura de contenido dentro del bloque en HTML                
    $div = '<div>';
// Creamos el contenedor para los selects    
    $divSelect = '<div class="divSelect">'; 

// El boton con onClick a la función show_info (En la variable $script1)    
    $select_button = '<button type="button" style="float: right" onclick="show_time()">' . get_string('ButtonTextShow', 'block_user_dedication') .'</button>';

// En primer lugar almacenamos los hijos en una variable
    $child_users = $this->get_child_users();
      
// Creamos la nueva array donde almacenaremos los identificadores de los usuarios hijos/tutorados
    $identificadoresUser = array();

// Almacenamos cada identificador de los hijos recorriendo cada hijo y sacando exclusivamente el id.
    $i = 0;
    foreach ($child_users as $child) 
      {
      $identificadoresUser[$i++] = $child->instanceid;
      }
    $select_user_options = '';
// Creamos un option por cada usuario que este relacionado con el hijo      
      foreach ($child_users as $child_user) 
        { 
        $select_user_options .= '<option value="' . $child_user->instanceid . '">' . $child_user->firstname. ' '. $child_user->lastname. '</option>';            
        }
// Creamos un div para almacenar el texto y select de alumno        
      $divName = '<div>'; 
      $studentname = get_string('StudentName', 'block_user_dedication') . ": ";  
// Creamos el select y le pasamos las options
      $select_user_html = '<select id="myUserSelect"name="selector1">'.$select_user_options."</select>";
      $divNameEnd = '</div>'; 
      
// Creamos un div para el texto y el select del día   
      $divDedicationDay = '<div>'; 
      $dedicationDay = get_string('DedicationDay', 'block_user_dedication') . ": "; 
      $select_day = '<input type="date" id="myDay" onchange="deleteDates(\'myDay\')">';// Si no tiene value no devuelve nada
      $divDedicationDayEnd = '</div>'; 
// Creamos el div para el texto y el select de la semana
      $divWeekDate = '<div>'; 
      $dedicationWeek = get_string('DedicationWeek', 'block_user_dedication') . ": "; 
      $select_week = '<input type="week" id="myWeek" onchange="deleteDates(\'myWeek\')">';
      $divWeekEnd = '</div>'; 

// Creamos el div para el texto y el select del mes
      $divMonth = '<div>';
      $dedicationMonth = get_string('DedicationMonth', 'block_user_dedication') . ": "; 
      $select_month = '<input type="month" id="myMonth" onchange="deleteDates(\'myMonth\')">';
      $divMonthEnd = '</div>'; 

// Finaliza el contenedor de los select
      $divSelectEnd = '</div>'; 

      $divCanvas = '<div class="divCanvas">'; 
// Creación del contenedor para mostrar la infomación filtrada por los select
      $canvas = '<canvas id="parentCanvas" width="1240" height="600" ></canvas>';

      $divCanvasEnd = '</div>'; 

      $div_end = '</div>'; 
// Recogemos el contenido
      $this->content = new stdClass;
      $this->content->text =  $script7 . $script1 . $script2 . $script3 . $script4 . $script5 . $script6 .$style . $div .$divSelect. $divName . $studentname . $select_user_html . $divNameEnd;
      $this->content->text .=  $divDedicationDay . $dedicationDay . $select_day . $divDedicationDayEnd . $divWeekDate . $dedicationWeek . $select_week . $divWeekEnd . $divMonth;
      $this->content->text .=   $dedicationMonth . $select_month . $divMonthEnd . $select_button  . $divSelectEnd . $divCanvas . $canvas . $divCanvasEnd . $div_end;
// Enviamos el contenido       
      return $this->content;
    }

// Función para obtener los usuarios hijo que estan asociados al padre
function get_child_users() 
  {
  global $CFG, $USER, $DB;
    
// Recuperamos los campos del usuario         
  $userfieldsapi = \core_user\fields::for_name();
  $allusernames = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
// Hacemos un select de la información de los hijos del usuario actual enlazando los role_assignments, context y user. 
  if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid, $allusernames
                                                FROM {role_assignments} ra, {context} c, {user} u
                                               WHERE ra.userid = ?
                                                     AND ra.contextid = c.id
                                                     AND c.instanceid = u.id
                                                     AND c.contextlevel = ".CONTEXT_USER, array($USER->id))) {
    }
    
      return $usercontexts;
      }
}  