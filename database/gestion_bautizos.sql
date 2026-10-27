create database gestion_bautizos character set utf8mb4 collate utf8mb4_unicode_ci;

use gestion_bautizos;

create table personas (
    id_persona int unsigned auto_increment primary key,
    nombre varchar(100) not null,
    apellido_paterno varchar(100) not null,
    apellido_materno varchar(100),
    fecha_nacimiento date not null,
    sexo enum('masculino','femenino','otro') not null,
    curp varchar(18),
    telefono varchar(15),
    correo varchar(150),
    fecha_registro timestamp not null default current_timestamp,
    activo boolean not null default true,
    unique (curp),
    unique (correo)
);

create table direcciones (
    id_direccion int unsigned auto_increment primary key,
    id_persona int unsigned not null,
    calle varchar(150) not null,
    numero_exterior varchar(20) not null,
    numero_interior varchar(20),
    colonia varchar(100) not null,
    codigo_postal varchar(10) not null,
    municipio varchar(100) not null,
    estado varchar(100) not null,
    referencias varchar(255),
    constraint fk_direcciones_personas foreign key (id_persona) references personas(id_persona) on update cascade on delete cascade
);

create table usuarios (
    id_usuario int unsigned auto_increment primary key,
    id_persona int unsigned not null,
    nombre_usuario varchar(50) not null,
    contrasena varchar(255) not null,
    activo boolean not null default true,
    ultimo_acceso datetime,
    fecha_creacion timestamp not null default current_timestamp,
    unique (id_persona),
    unique (nombre_usuario),
    constraint fk_usuarios_personas foreign key (id_persona) references personas(id_persona) on update cascade on delete restrict
);

create table roles (
    id_rol int unsigned auto_increment primary key,
    nombre varchar(50) not null,
    descripcion varchar(255),
    unique (nombre)
);

create table usuario_rol (
    id_usuario int unsigned not null,
    id_rol int unsigned not null,
    primary key (id_usuario, id_rol),
    constraint fk_usuario_rol_usuario foreign key (id_usuario) references usuarios(id_usuario) on update cascade on delete cascade,
    constraint fk_usuario_rol_rol foreign key (id_rol) references roles(id_rol) on update cascade on delete restrict
);

create table sacerdotes (
    id_sacerdote int unsigned auto_increment primary key,
    id_persona int unsigned not null,
    numero_identificacion varchar(50) not null,
    activo boolean not null default true,
    unique (id_persona),
    unique (numero_identificacion),
    constraint fk_sacerdotes_personas foreign key (id_persona) references personas(id_persona) on update cascade on delete restrict
);

create table secretarias (
    id_secretaria int unsigned auto_increment primary key,
    id_persona int unsigned not null,
    puesto varchar(100) not null,
    activo boolean not null default true,
    unique (id_persona),
    constraint fk_secretarias_personas foreign key (id_persona) references personas(id_persona) on update cascade on delete restrict
);

create table padres_tutores (
    id_padre_tutor int unsigned auto_increment primary key,
    id_persona int unsigned not null,
    ocupacion varchar(100),
    activo boolean not null default true,
    unique (id_persona),
    constraint fk_padres_tutores_personas foreign key (id_persona) references personas(id_persona) on update cascade on delete restrict
);

create table padrinos (
    id_padrino int unsigned auto_increment primary key,
    id_persona int unsigned not null,
    estado_civil varchar(50),
    activo boolean not null default true,
    unique (id_persona),
    constraint fk_padrinos_personas foreign key (id_persona) references personas(id_persona) on update cascade on delete restrict
);

create table bautizados (
    id_bautizado int unsigned auto_increment primary key,
    id_persona int unsigned not null,
    lugar_nacimiento varchar(150),
    observaciones text,
    unique (id_persona),
    constraint fk_bautizados_personas foreign key (id_persona) references personas(id_persona) on update cascade on delete restrict
);

create table estados_solicitud (
    id_estado_solicitud int unsigned auto_increment primary key,
    nombre varchar(50) not null,
    descripcion varchar(255),
    unique (nombre)
);

create table solicitudes_bautismo (
    id_solicitud int unsigned auto_increment primary key,
    folio varchar(50) not null,
    id_bautizado int unsigned not null,
    fecha_solicitud date not null,
    id_estado_solicitud int unsigned not null,
    observaciones text,
    creado_por int unsigned not null,
    fecha_creacion timestamp not null default current_timestamp,
    fecha_actualizacion timestamp not null default current_timestamp on update current_timestamp,
    unique (folio),
    constraint fk_solicitudes_bautizado foreign key (id_bautizado) references bautizados(id_bautizado) on update cascade on delete restrict,
    constraint fk_solicitudes_estado foreign key (id_estado_solicitud) references estados_solicitud(id_estado_solicitud) on update cascade on delete restrict,
    constraint fk_solicitudes_usuario foreign key (creado_por) references usuarios(id_usuario) on update cascade on delete restrict
);

create table solicitud_tutores (
    id_solicitud int unsigned not null,
    id_padre_tutor int unsigned not null,
    parentesco varchar(50) not null,
    tutor_principal boolean not null default false,
    primary key (id_solicitud, id_padre_tutor),
    constraint fk_solicitud_tutores_solicitud foreign key (id_solicitud) references solicitudes_bautismo(id_solicitud) on update cascade on delete cascade,
    constraint fk_solicitud_tutores_tutor foreign key (id_padre_tutor) references padres_tutores(id_padre_tutor) on update cascade on delete restrict
);

create table solicitud_padrinos (
    id_solicitud int unsigned not null,
    id_padrino int unsigned not null,
    tipo_participacion enum('padrino','madrina') not null,
    primary key (id_solicitud, id_padrino),
    constraint fk_solicitud_padrinos_solicitud foreign key (id_solicitud) references solicitudes_bautismo(id_solicitud) on update cascade on delete cascade,
    constraint fk_solicitud_padrinos_padrino foreign key (id_padrino) references padrinos(id_padrino) on update cascade on delete restrict
);

create table tipos_documento (
    id_tipo_documento int unsigned auto_increment primary key,
    nombre varchar(100) not null,
    descripcion varchar(255),
    obligatorio boolean not null default true,
    activo boolean not null default true,
    unique (nombre)
);

create table documentos (
    id_documento int unsigned auto_increment primary key,
    id_solicitud int unsigned not null,
    id_persona int unsigned not null,
    id_tipo_documento int unsigned not null,
    nombre_archivo varchar(255) not null,
    ruta_archivo varchar(500) not null,
    extension varchar(20),
    mime_type varchar(100),
    tamano_bytes bigint unsigned,
    estado_revision enum('pendiente','aprobado','rechazado') not null default 'pendiente',
    fecha_subida timestamp not null default current_timestamp,
    revisado_por int unsigned,
    fecha_revision datetime,
    observaciones_revision text,
    constraint fk_documentos_solicitud foreign key (id_solicitud) references solicitudes_bautismo(id_solicitud) on update cascade on delete cascade,
    constraint fk_documentos_persona foreign key (id_persona) references personas(id_persona) on update cascade on delete restrict,
    constraint fk_documentos_tipo foreign key (id_tipo_documento) references tipos_documento(id_tipo_documento) on update cascade on delete restrict,
    constraint fk_documentos_revisor foreign key (revisado_por) references usuarios(id_usuario) on update cascade on delete set null
);

create table horarios_bautizo (
    id_horario int unsigned auto_increment primary key,
    fecha date not null,
    hora_inicio time not null,
    hora_fin time not null,
    capacidad int unsigned not null,
    disponible boolean not null default true,
    observaciones varchar(255),
    constraint chk_horarios_capacidad check (capacidad > 0),
    constraint chk_horarios_horas check (hora_fin > hora_inicio),
    unique (fecha, hora_inicio)
);

create table citas (
    id_cita int unsigned auto_increment primary key,
    id_solicitud int unsigned not null,
    id_horario int unsigned not null,
    id_sacerdote int unsigned not null,
    estado enum('programada','confirmada','realizada','cancelada','reprogramada') not null default 'programada',
    fecha_creacion timestamp not null default current_timestamp,
    fecha_actualizacion timestamp not null default current_timestamp on update current_timestamp,
    observaciones text,
    unique (id_solicitud),
    constraint fk_citas_solicitud foreign key (id_solicitud) references solicitudes_bautismo(id_solicitud) on update cascade on delete restrict,
    constraint fk_citas_horario foreign key (id_horario) references horarios_bautizo(id_horario) on update cascade on delete restrict,
    constraint fk_citas_sacerdote foreign key (id_sacerdote) references sacerdotes(id_sacerdote) on update cascade on delete restrict
);

create table historial_citas (
    id_historial int unsigned auto_increment primary key,
    id_cita int unsigned not null,
    id_usuario int unsigned not null,
    accion varchar(100) not null,
    detalle text,
    fecha_accion timestamp not null default current_timestamp,
    constraint fk_historial_cita foreign key (id_cita) references citas(id_cita) on update cascade on delete cascade,
    constraint fk_historial_usuario foreign key (id_usuario) references usuarios(id_usuario) on update cascade on delete restrict
);

create table bautismos (
    id_bautismo int unsigned auto_increment primary key,
    id_cita int unsigned not null,
    fecha_realizacion date not null,
    folio_acta varchar(50) not null,
    numero_libro varchar(50) not null,
    numero_pagina varchar(50) not null,
    observaciones text,
    registrado_por int unsigned not null,
    fecha_registro timestamp not null default current_timestamp,
    unique (id_cita),
    unique (folio_acta),
    constraint fk_bautismos_cita foreign key (id_cita) references citas(id_cita) on update cascade on delete restrict,
    constraint fk_bautismos_usuario foreign key (registrado_por) references usuarios(id_usuario) on update cascade on delete restrict
);