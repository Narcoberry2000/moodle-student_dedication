
// Array de colores para diferenciar las barras de tiempo por cada curso
const colours = ['Tan', 'DarkSlateBlue', 'LimeGreen', 'Crimson', 'MediumVioletRed', 
		  'Orange', 'MediumOrchid', 'Gray', 'Gold', 'SpringGreen',
		  'Black', 'Teal', 'Indigo', 'Olive', 'Sienna', 
		  'SteelBlue', 'Salmon']

// ********************************************          
// Función encargada de dibujar toda la grafica
// ********************************************
function drawDedication(myObj)
	{
// Parseamos el JSON y lo pasamos objeto      
    const myJSON = JSON.parse(myObj);
// Seleccionamos el canvas sobre el que vamos a dibujar    
	const canvas = document.getElementById("parentCanvas");
// Creamos el contexto    
    const ctx = canvas.getContext("2d");
// Ponemos el contexto por defecto (esto para borrar el dibujo anterior y no sobreponer las graficas)    
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
// Utilizamos transform para cambiar la orientación del canvas    
    ctx.transform(1, 0, 0, -1, 0, canvas.height);
// Creamos un margen y se lo aplicamos a los cardinales de la Zona1    
    marginCanvas = 25;
    xZona1 = marginCanvas;
    yZona1 = marginCanvas;
// Creamos las dos zonas realizando calculos para que estos sean responsive al tamaño de la pagina (canvas.width y canvas.height)    
    widthZona1 = 0.7 * (canvas.width-2*marginCanvas);
    heightZona1 = (canvas.height-2*marginCanvas); 
    xZona2 = xZona1 + widthZona1 + marginCanvas;
    yZona2 = marginCanvas +  0.25 * (canvas.height-2*marginCanvas);
    widthZona2 = canvas.width - widthZona1 - 3 * marginCanvas;
    heightZona2 = 0.5 * (canvas.height-2*marginCanvas);

// Sacamos la cantidad de elementos en dedication (para diferenciar si se trata de dias, semanas o meses)    
    dedicationElements = myJSON["cursos"][0]["dedicacion"].length;
	yArray = [];
// Creamos una array vacia con el numero de elementos
	for (let i=0; i < dedicationElements; i++)
       	yArray[i] = 0;

// Recorremos los cursos y metemos en cada campo el tiempo dedicado para cada curso        
	for (let i=0; i < myJSON["cursos"].length; i++)
    	{
		for (let j=0; j < dedicationElements; j++)
        	yArray[j] += myJSON["cursos"][i]["dedicacion"][j]     
        }

// Recogemos el valor maximo del array (para la grafica de semanas y dias poner el maximo y no el total de horas de cada día
// para mejorar la visibilidad del grafico y que no se vea demasiado pequeño)        
    let ymax = Math.max(...yArray);
// Pasamos a horas y le sumamos una (para tener siempre una hora mas del tiempo maximo empleado)    
    ymax = Math.trunc(ymax / 3600) + 1;
    
	
// Creamos un rectangulo para la primera y segunda zona (la zona de la grafica y la zona para visibilizar a que curso corresponde
// cada barra)
    ctx.beginPath();
    ctx.rect(xZona1, yZona1, widthZona1, heightZona1);
    ctx.stroke();

    ctx.beginPath();
// Utilizamos clearRect para que no se vean los bordes de esta zona    
    ctx.clearRect(xZona2, yZona2, widthZona2, heightZona2);
    ctx.stroke();

// Función para crear el eje vertical (lo creamos en base al tamaño de la zona1)    
    drawYAxis(ctx, myJSON, yZona1, yZona1 + heightZona1, xZona1, ymax);
// Función para crear el eje horizontal (lo creamos en base al tamaño de la zona1)        
    drawXAxis(ctx, myJSON, xZona1, xZona1 + widthZona1, yZona1, 2 * yZona1 + heightZona1 - 6);
// Función para dibujar las barras representando el tiempo (tambien se crea en base al tamaño de la zona1)  
    drawBlocks(ctx, myJSON, yZona1, yZona1 + heightZona1, xZona1, xZona1  + widthZona1, ymax, colours);
// Función para crear la leyenda en la que mostrar los cursos (lo creamos en base al tamaño de la zona2)    
    drawLeyenda(ctx, myJSON, yZona2, yZona2 + heightZona2, xZona2, xZona2  + widthZona2, colours);
    }


// **********************************
// Función para crear el eje vertical
// **********************************

function drawYAxis(ctx, myJSON, ymin, ymax, xpos, ymaxHour)
	{
// Recogemos el canvas en una constante        
    const canvas = document.getElementById("parentCanvas");
// Recogemos la cantidad de elementos de dedicación    
    dedicationElements = myJSON["cursos"][0]["dedicacion"].length;

// Creamos una linea desde el punto minimo de y hasta el maximo de y (todo en la misma posición de x)    
    ctx.beginPath();
    ctx.moveTo(xpos, ymin);
    ctx.lineTo(xpos, ymax);
    ctx.lineWidth = 2;
	ctx.strokeStyle = 'black';
	ctx.stroke();

// Diferenciamos los tipos para saber cuanto hay que dividir la linea    
   if(myJSON['type'] == 'day')
   		{
// Creamos una variable ypos para tener referencia de la posición anterior a la hora de dibujar                        
        ypos = ymin;
// Creamos una variable para sacar la diferencia entre el tamaño del canvas y lo dividimos entre las 60 minutos        
    	dist = (ymax-ypos)/60;
    	ctx.font = "12px Arial";
    	ctx.lineWidth = 1.5;
    	ctx.strokeStyle = 'black';

// Realizamos un bucle para crear cada una de las lineas para los 60 minutos        
        for(let i=0; i <= 60; i++)
            { 
// Iniciamos el camino desde ypos (que inicia en ymin)                     
            ctx.beginPath();
            ctx.moveTo(xpos, ypos);
// Comprobamos para diferenciar las lineas que corresponden a multiplos de 5            
            if(i % 5 == 0)
                {
// Si son minutos multiplos de 5 hacemos la linea mas larga                    
                ctx.lineTo(xpos - 14,ypos);
                }
            else
                {
                ctx.lineTo(xpos - 6,ypos);
                }
            ctx.lineWidth = 1.5;   
            ctx.strokeStyle = 'black';    
            ctx.stroke();
// Ademas, si son multiplos de 5 añadimos un texto
            if(i % 5 == 0)
                {
// Para crear el texto es necesario guardar el contenido del canvas y girarlo (esto debido a que tal como se encuentra el canvas el texto aparece del reves)                    
                ctx.save();
                ctx.transform(1, 0, 0, -1, 0, canvas.height);
                ctx.fillStyle = "black";
// Escribimos el texto que corresponde a i (el numero de los minutos)                
                ctx.fillText(i ,xpos - 18, ymax - ypos + ymin - 2);
// Y restauramos el contexto tal como se encontraba anteriormente                
                ctx.restore();
                }
// Por ultimo a ypos le añadimos la distancia (Para que vaya subiendo sobre el eje vertical)                         
            ypos += dist;
            }
        }
// Si es de tipo semana o mes
    else
    	{
// Creamos ypos de nuevo            
        ypos = ymin;
    	
    	ctx.font = "12px Arial";
    	ctx.lineWidth = 1.5;
    	ctx.strokeStyle = 'black';
// Creamos ymasHour y lo multiplicamos por 4 (para dibujar una linea por cada cuarto de hora)
		ymaxHour = ymaxHour * 4;
// Creamos la distancia        
        dist = (ymax-ypos) / ymaxHour;
// Creamos un bucle por la cantidad de horas maxima recogidas        
        for(let i=0; i <= ymaxHour; i++)
            {                  
            ctx.beginPath();
            ctx.moveTo(xpos, ypos);
// Si es multiplo de 4 se crea una linea mas grande (las que corresponde a horas en punto)            
            if(i % 4 == 0)
                {
                ctx.lineTo(xpos - 14,ypos);
                }
            else
                {
                ctx.lineTo(xpos - 6,ypos);
                }            
            ctx.lineWidth = 1.5;   
            ctx.strokeStyle = 'black';    
            ctx.stroke();
// De igual forma que anteriormente escribimos la hora a la que correponde          
            if (i % 4 == 0)
                {            
            	ctx.save();
            	ctx.transform(1, 0, 0, -1, 0, canvas.height);
            	ctx.fillStyle = "black";
            	ctx.fillText(i / 4 ,xpos - 18, ymax - ypos + ymin - 2);
            	ctx.restore();
                }
               
            ypos += dist;
            }
        }       
    }

// ************************************    
// Función para crear el eje horizontal
// ************************************    

function drawXAxis(ctx, myJSON, xmin, xmax, ypos, numPos)
	{
    const canvas = document.getElementById("parentCanvas");
    
    dedicationElements = myJSON["cursos"][0]["dedicacion"].length;

// Creamos la linea pero esta vez desde xmin hasta xmax    
    ctx.beginPath();
    ctx.moveTo(xmin, ypos);
    ctx.lineTo(xmax, ypos);
    ctx.lineWidth = 2;
	ctx.strokeStyle = 'black';
	ctx.stroke();

// Creamos xpos para desplazarnos iniciando desde xmin    
    xpos = xmin;
// Creamos la distancia, esta vez la cantidad depende de la cantidad de elementos
// Cambiara a las horas del día, dias de la semana o del mes    
    dist = (xmax-xpos)/dedicationElements;
    ctx.font = "12px Arial";
    ctx.lineWidth = 1.5;
    ctx.strokeStyle = 'black';
// Creamos una variable para que el contador no empiece en 0 a la hora de mostrar los textos    
    sumarUna = 0;
// Preguntamos si son tipo week o month    
    if (myJSON["type"] == "week" || myJSON["type"] == "month")
// Añadimos uno a la variable    
    	sumarUna = 1;    
// Creamos un bucle por tantos como elementos    
    for (let i=0; i <= dedicationElements; i++)
    	{

// Empezamos a dibujar lineas verticales donde se encuentre xpos            
        ctx.beginPath();
    	ctx.moveTo(xpos, ypos);

        ctx.lineTo(xpos ,ypos - 10);
            
        ctx.lineWidth = 1.5;   
        ctx.strokeStyle = 'black';    
		ctx.stroke();
// Realizamos lo mismo que anteriormente para escribir texto        
        ctx.save();
        ctx.transform(1, 0, 0, -1, 0, canvas.height);
        ctx.fillStyle = "black";
		
		if (i < dedicationElements)
        	{
        	if ( i < 10)
// Diferenciamos la posición del texto depediendo de la cantidad de caracteres            
				ctx.fillText(i + sumarUna ,xpos + dist / 2 - 5, numPos);
        	else			
        		ctx.fillText(i + sumarUna ,xpos + dist / 2 - 8, numPos);
            }
        
        ctx.restore();  
// Por ultimo sumamos la distancia a la posición para desplazarnos por la horizontal        
        xpos += dist;
        }   
    }

// **********************************************    
// Función para crear los bloques de las graficas
// **********************************************

function drawBlocks(ctx, myJSON, ymin, ymax, xmin, xmax, ymaxHour, colours)
	{
// Diferenciamos por tipo               
    if(myJSON['type'] == 'day')
    	{
// Recogemos las posiciones de Y (esto para poder dibujar encima el tiempo empleado con otro curso diferente para un mismo dia u hora)            
        posY = [];

// Almacenamos la cantidad de elementos        
        dedicationElements = myJSON["cursos"][0]["dedicacion"].length;
// Inicializamos todas las posiciones de las graficas en el ymin        
        for (let i=0; i < dedicationElements; i++)
            posY[i] = ymin;

// Indicamos el ancho de los rectangulos tanto como las franjas de drawXAxis            
        xdist = (xmax - xmin) / dedicationElements;

// Creamos un bucle por la cantidad de cursos        
        for (let i=0; i < myJSON["cursos"].length; i++)
            {
            xpos = xmin;
// Creamos un bucle tambien por la cantidad de elementos            
            for (let j=0; j < dedicationElements; j++)
                {
// Indicamos la ydist en base al tiempo empleado en segundos (escalado al tamaño del canvas)                    
                ydist = myJSON["cursos"][i]["dedicacion"][j] * (ymax-ymin) / 3600;
                ctx.beginPath();
// Indicamos el color a usar para rellenar el rectangulo (recogido en el array de colours)                
                ctx.fillStyle = colours[i];
// Dibujamos un rectangulo con relleno                
                ctx.fillRect(xpos, posY[j], xdist, ydist);
//Añadir rectangulo solo bordes                
                ctx.strokeRect(xpos, posY[j], xdist, ydist);
                ctx.stroke();
                posY[j] += ydist;
                xpos += xdist;
                }
            }
        }
// Si se trata de semanas o meses        
    else
        {
        posY = [];

        dedicationElements = myJSON["cursos"][0]["dedicacion"].length;
        for (let i=0; i < dedicationElements; i++)
            posY[i] = ymin;

        xdist = (xmax - xmin) / dedicationElements;
// Bucle para cursos
        for (let i=0; i < myJSON["cursos"].length; i++)
            {
            xpos = xmin;
// Bucle para elementos            
            for (let j=0; j < dedicationElements; j++)
                {
// La distancia tambien se escala en base al ymaxHour (la cantidad de horas maxima recogida en todo el array)                    
                ydist = myJSON["cursos"][i]["dedicacion"][j] * (ymax-ymin) / (ymaxHour * 3600);
                ctx.beginPath();
                ctx.fillStyle = colours[i];
                ctx.fillRect(xpos, posY[j], xdist, ydist);
                ctx.strokeRect(xpos, posY[j], xdist, ydist);
                ctx.stroke();
                posY[j] += ydist;
                xpos += xdist;
                }
             }
        }
    }

// ******************************************************************* 
// Función para dibujar la leyenda donde ver los nombres de los cursos   
// *******************************************************************

 function drawLeyenda(ctx, myJSON, ymin, ymax, xmin, xmax, colours) 
 	{
    const canvas = document.getElementById("parentCanvas");
// Indicamos el margin de los elementos    
    marginLeyenda = 10;
// Los tamaños que tendran los rectangulos para ver el color    
    widthRect = 40;
    heightRect = 20;
// Calculamos la posición (es necesario restar el tamaño del rectangulo debido a donde se encuentra el punto de inicio del dibujo del rectangulo)    
    ypos = ymax - marginLeyenda - heightRect;
    xpos = xmin + marginLeyenda;
// Realizamos un bucle por alumnos    
    for (let i=0; i < myJSON["cursos"].length; i++)
        {
// Dibujamos los rectangulos (Tambien recogiendo los colores)            
        ctx.beginPath();
        ctx.fillStyle = colours[i];
        ctx.fillRect(xpos, ypos, widthRect, heightRect);
        ctx.strokeRect(xpos, ypos, widthRect, heightRect );
        ctx.stroke();
		
// Y dibujamos los textos sobre el curso (utilizando el mismo metodo que anteriormente)        
        ctx.save();
        ctx.transform(1, 0, 0, -1, 0, canvas.height);
        ctx.fillStyle = "black";
// Para la posición es necesario tener el cuenta el widthReact para no dibujarlo debajo        
		ctx.fillText(myJSON["cursos"][i].name , xpos + widthRect + 10 , ymax - ypos + ymin);
        ctx.restore();
        ypos = ypos - marginLeyenda - heightRect;
        }
    
    
    }
