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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Spanish language strings.
 *
 * @package availability_managed
 * @copyright 2026 Juan Luis Simon
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Disponibilidad gestionada';
$string['closed'] = 'Cerrado';
$string['close'] = 'Cerrar';
$string['courseconfiguration'] = 'Configuración de Disponibilidad gestionada';
$string['courseconfiguration_desc'] = 'Active o desactive la gestión centralizada para este curso.';
$string['courseenabled'] = 'Disponibilidad gestionada se ha activado en el curso.';
$string['coursedisabled'] = 'Disponibilidad gestionada se ha desactivado en el curso.';
$string['coursedisabled_error'] = 'Disponibilidad gestionada no está activada en este curso.';
$string['coursedisabled_notice'] = 'Disponibilidad gestionada no está activada en este curso. Active la herramienta antes de configurar el acceso a secciones y actividades.';
$string['currentdefaultopen'] = 'El contenido nuevo se abre inicialmente para toda la clase.';
$string['currentdefaultclosed'] = 'El contenido nuevo se crea inicialmente cerrado.';
$string['disableconfirm'] = '¿Confirma que desea desactivar Disponibilidad gestionada? Se eliminarán sus reglas y condiciones de este curso. Las demás restricciones de Moodle se conservarán sin cambios.';
$string['disablecourse'] = 'Desactivar en este curso';
$string['disablecourse_desc'] = 'Se eliminarán las condiciones y reglas gestionadas. No se modificarán fechas, finalización, calificaciones ni otras restricciones de Moodle.';
$string['enablecourse'] = 'Activar en este curso';
$string['initialstate'] = 'Estado inicial del contenido';
$string['initialstate_desc'] = 'Esta elección se aplicará a todas las secciones, actividades y recursos existentes, y será el estado inicial del contenido nuevo.';
$string['initialopen'] = 'Mantener todo el contenido abierto';
$string['initialopen_desc'] = 'Opción recomendada para cursos existentes. La activación no cerrará contenido a los alumnos.';
$string['initialclosed'] = 'Comenzar con todo el contenido cerrado';
$string['initialclosed_desc'] = 'Ningún alumno superará la condición gestionada hasta que configure destinatarios. Utilícelo solo si desea preparar la apertura desde cero.';
$string['statusenabled'] = 'Estado: activado';
$string['statusdisabled'] = 'Estado: desactivado';
$string['applychildren'] = 'Copiar la configuración a las actividades';
$string['alreadyclosed'] = 'Ya cerrado';
$string['alreadyclosed_help'] = 'Este elemento ya está cerrado para todos.';
$string['alreadyopen'] = 'Ya abierto';
$string['alreadyopen_help'] = 'Este elemento ya está abierto para toda la clase.';
$string['auditlog'] = 'Historial';
$string['applychildrenconfirm'] = '¿Reemplazar la configuración de Disponibilidad gestionada de todas las actividades de esta sección?';
$string['applychildrendone'] = 'Configuración aplicada a {$a} actividades.';
$string['sectioncontrolhint'] = 'La sección controla el acceso a todo su contenido.';
$string['sectionclosedmodules'] = 'Mientras la sección esté cerrada no se puede acceder a sus actividades, por eso sus controles están ocultos.';
$string['whyhidden'] = '¿Por qué?';
$string['modulecontrolshelp'] = 'La sección permite entrar. Ahora puede definir un acceso más específico para cada actividad; estas reglas no pueden dar acceso a quien esté excluido de la sección.';
$string['closeeveryone'] = 'Cerrar para todos';
$string['edititem'] = 'Editar disponibilidad de {$a}';
$string['everyone'] = 'Toda la clase';
$string['groups'] = 'Grupos';
$string['help'] = 'Ayuda de Disponibilidad gestionada';
$string['helptitle'] = 'Cómo usar Disponibilidad gestionada';
$string['helpintro'] = 'Controle desde una sola pantalla quién puede acceder a cada sección y actividad del curso.';
$string['openhelp'] = 'Ayuda: cómo utilizar esta herramienta';
$string['gotodashboard'] = 'Ir al panel de gestión';
$string['helpkeytitle'] = 'Idea clave';
$string['helpkeytext'] = 'Esta herramienta puede restringir más el acceso, pero nunca elimina las demás restricciones de Moodle. Si una actividad tiene además una fecha, una calificación o una condición de finalización, el alumno debe cumplir también esas condiciones.';
$string['helpwhat_title'] = 'Qué puede controlar';
$string['helpwhat_text'] = 'Puede configurar secciones, actividades y recursos para uno o varios destinatarios:';
$string['helpwhat_everyone'] = 'Toda la clase: cualquier alumno matriculado supera esta condición.';
$string['helpwhat_groups'] = 'Grupos: acceden los alumnos que pertenezcan actualmente a cualquiera de los grupos elegidos.';
$string['helpwhat_users'] = 'Alumnos: acceden únicamente los alumnos seleccionados de forma individual.';
$string['helpwhat_closed'] = 'Cerrado: nadie supera esta condición hasta que usted añada un destinatario.';
$string['helpedit_title'] = 'Configurar una sección o actividad';
$string['helpedit_step1'] = 'Localice el contenido con el buscador o dentro de su sección.';
$string['helpedit_step2'] = 'Pulse el botón que muestra su estado actual.';
$string['helpedit_step3'] = 'Elija Toda la clase o seleccione los grupos y alumnos que deban acceder.';
$string['helpedit_step4'] = 'Pulse Guardar. El nuevo acceso se aplica inmediatamente.';
$string['helpfast_title'] = 'Acciones rápidas';
$string['helpfast_open'] = 'Sustituye la configuración actual del elemento y permite el acceso a toda la clase.';
$string['helpfast_close'] = 'Elimina todos sus destinatarios gestionados y deja el elemento cerrado.';
$string['helpfast_apply'] = 'Copia la configuración de la sección a todas sus actividades. Solo ocurre cuando usted lo confirma; los cambios posteriores no se sincronizan automáticamente.';
$string['helpadvance_title'] = 'Avanzar un grupo o un alumno';
$string['helpadvance_text'] = 'Las vistas por grupo y alumno permiten abrir manualmente la siguiente sección que todavía esté cerrada para ese destinatario.';
$string['helpadvance_step1'] = 'Elija Vista por grupo o Vista por alumno.';
$string['helpadvance_step2'] = 'Seleccione el destinatario y revise qué secciones tiene abiertas.';
$string['helpadvance_step3'] = 'Pulse Abrir siguiente sección. No depende de notas ni de finalización: la decisión siempre es manual.';
$string['helpcare_title'] = 'Antes de guardar, tenga en cuenta';
$string['helpcare_other'] = 'Las restricciones normales de Moodle continúan activas y se combinan con esta configuración.';
$string['helpcare_section'] = 'Cerrar una sección impide en la práctica acceder a su contenido, aunque alguna actividad interior figure como abierta.';
$string['helpcare_groups'] = 'La pertenencia a grupos se consulta en Moodle en cada acceso. Si un alumno cambia de grupo, su acceso también cambia.';
$string['helpcare_bulk'] = 'Copiar la configuración a las actividades reemplaza la configuración gestionada de cada actividad de esa sección. Revise la confirmación antes de continuar.';
$string['helpexamples_title'] = 'Ejemplos habituales';
$string['helpexample_group_title'] = 'Liberar una unidad para el Grupo A';
$string['helpexample_group_text'] = 'Edite la sección, seleccione Grupo A y guarde. Eso basta para controlar el acceso a todo su contenido. Configure actividades individuales solo cuando alguna necesite una regla diferente.';
$string['helpexample_user_title'] = 'Dar acceso excepcional a un alumno';
$string['helpexample_user_text'] = 'Edite el elemento, busque al alumno, selecciónelo y guarde. Los demás alumnos seguirán cerrados salvo que tengan acceso por toda la clase o por un grupo seleccionado.';
$string['globallyenabled'] = 'Activar Disponibilidad gestionada';
$string['globallyenabled_desc'] = 'Al desactivarlo, las condiciones gestionadas dejan de restringir temporalmente el acceso en todo el sitio. Se conservan las reglas y estados de los cursos, continúan aplicándose las demás restricciones de Moodle y los cursos se reconcilian automáticamente al reactivarlo.';
$string['globalstatus'] = 'Estado general del sitio';
$string['globalsuspended_notice'] = 'Un administrador ha suspendido temporalmente Disponibilidad gestionada. Las reglas gestionadas no están restringiendo el acceso y este panel queda en modo informativo.';
$string['globalsuspended_config'] = 'Disponibilidad gestionada está suspendida temporalmente en todo el sitio. No se puede cambiar la activación de este curso hasta que un administrador la reactive.';
$string['globalsuspended_error'] = 'Disponibilidad gestionada está suspendida temporalmente en todo el sitio.';
$string['managedcoursescount'] = 'Cursos que conservan la gestión activada: {$a}';
$string['privacy:metadata:availability_managed_audit'] = 'Cambios funcionales realizados por profesores y gestores.';
$string['privacy:metadata:availability_managed_audit:userid'] = 'El usuario que realizó el cambio.';
$string['privacy:metadata:availability_managed_rule'] = 'Reglas de disponibilidad dirigidas a usuarios.';
$string['privacy:metadata:availability_managed_rule:scopeid'] = 'El ID del usuario objetivo cuando el ámbito es usuario.';
$string['groupview'] = 'Vista por grupo';
$string['groupscount'] = 'Grupos: {$a}';
$string['manageavailability'] = 'Gestionar disponibilidad';
$string['openeveryone'] = 'Abrir para toda la clase';
$string['open'] = 'Abierto';
$string['openaction'] = 'Abrir';
$string['opennext'] = 'Abrir siguiente sección';
$string['opennextdone'] = 'Sección abierta: {$a}';
$string['opennextnone'] = 'Todas las secciones ya están abiertas para este objetivo.';
$string['selecttarget'] = 'Seleccione un grupo o alumno';
$string['save'] = 'Guardar';
$string['searchcontent'] = 'Buscar contenido';
$string['users'] = 'Alumnos';
$string['userview'] = 'Vista por alumno';
$string['userscount'] = 'Alumnos: {$a}';
$string['description'] = 'Permitido por Disponibilidad gestionada';
$string['requires_managed'] = 'Este contenido no está disponible actualmente para usted.';
