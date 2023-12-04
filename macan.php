<?php

/*
 * Plugin Name: Mail plugin
 * Author:      Anatolii
 * Version:     1.0
 */

function form_styles()
{
    wp_enqueue_style('form-styles', plugin_dir_url(__FILE__) . 'assets/style.css');
}
add_action('wp_enqueue_scripts', 'form_styles');

function form_scripts()
{
    wp_enqueue_script('form-scripts', plugin_dir_url(__FILE__) . 'assets/custom.js', array('jquery'), '', true);
}
add_action('wp_enqueue_scripts', 'form_scripts');


register_activation_hook(__FILE__, 'macan_activate');

function macan_activate()
{

    add_option('macan_api_key', '');
    add_option('macan_email_recipient', '');

    // Редирект на страницу настроек после активации
    wp_redirect(admin_url('admin.php?page=macan-plugin-settings'));
    exit;
}

// Добавляем страницу настроек в меню администратора
add_action('admin_menu', 'macan_plugin_menu');

function macan_plugin_menu()
{
    add_menu_page('Настройки плагина Macan', 'Настройки плагина Macan', 'manage_options', 'macan-plugin-settings', 'macan_plugin_settings_page');
}

function macan_plugin_settings_page()
{
    ?>
    <div class="wrap">
        <h2>Настройки плагина</h2>
        <form method="post" action="options.php">
            <?php settings_fields('macan_plugin_settings_group'); ?>
            <?php do_settings_sections('macan-plugin-settings'); ?>
            <?php submit_button(); ?>
        </form>
        <?php
        // Получаем путь к файлу лога
        $log_file = plugin_dir_path(__FILE__) . 'log.txt';

        // Выводим ссылку на лог-файл, если он существует
        if (file_exists($log_file)) {
            $log_url = home_url('/wp-content/plugins/macan1/log.txt');

            echo '<p><a href="' . esc_url($log_url) . '" target="_blank">Ссылка на лог-файл</a></p>';

        }
        ?>
    </div>
    <?php
}

// Добавляем поля настроек
add_action('admin_init', 'macan_plugin_settings');

function macan_plugin_settings()
{
    register_setting('macan_plugin_settings_group', 'macan_api_key');
    register_setting('macan_plugin_settings_group', 'macan_email_recipient');

    add_settings_section('macan_plugin_settings_section', 'Настройки API и адреса приложения', 'macan_plugin_section_text', 'macan-plugin-settings');

    add_settings_field('macan_api_key', 'API Key:', 'macan_api_key_callback', 'macan-plugin-settings', 'macan_plugin_settings_section');
    add_settings_field('macan_email_recipient', 'Получатель письма:', 'macan_email_recipient_callback', 'macan-plugin-settings', 'macan_plugin_settings_section');
}

function macan_plugin_section_text()
{
    echo '<p>Укажите настройки API и адрес приложения</p>';
}

function macan_api_key_callback()
{
    echo '<input type="text" name="macan_api_key" value="' . esc_attr(get_option('macan_api_key')) . '" />';
}

function macan_email_recipient_callback()
{
    echo '<input type="text" name="macan_email_recipient" value="' . esc_attr(get_option('macan_email_recipient')) . '" />';
}



function form_shortcode_function()
{
    // Здесь разместите код, который будет обрабатывать ваш шорткод
    // Например, вывод формы
    $form_html = '<form id="macanForm" method="post">
        <label for="first_name">First Name*:</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="last_name">Last Name*:</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="email">E-mail*:</label>
        <input type="email" id="email" name="email" required>

        <label for="subject">Subject*:</label>
        <input type="text" id="subject" name="subject" required>

        <label for="message">Message*:</label>
        <textarea id="message" name="message" required></textarea>

        <input type="submit" value="Submit">
    </form>
	    <div id="formResult"></div>
		<script>
            jQuery(document).ready(function($) {
			
			$("#macanForm").submit(function() {
            var emailInput = $("input[type=\'email\']");
            var emailValue = emailInput.val();

            // Простая проверка на валидность email
            if (!isValidEmail(emailValue)) {
                // Выводим сообщение об ошибке
                $("#formResult").html("Пожалуйста, введите корректный email.");
                
                // Подсвечиваем поле красным
                emailInput.css("border-color", "red");

                // Останавливаем отправку формы
                return false;
            }

                                var formData = $(this).serialize();
                    $.ajax({
                        type: "POST",
                        url: "' . admin_url('admin-ajax.php') . '",
                        data: "action=process_macan_form&" + formData,
                        success:function(data){
                            $("#formResult").html(data);
                        },
						 error: function() {
            				$("#formResult").html("Произошла ошибка при отправке формы");
        					}
                    });
                    return false;

            return false;
        });

        // Функция для проверки валидности email
        function isValidEmail(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Обработчик для удаления подсветки при фокусе на поле
        $("input[type=\'email\']").focus(function() {
            $(this).css("border-color", ""); // Убираем цвет рамки
        });
			
			// Обработчик для проверки валидности email при потере фокуса
        $("input[type=\'email\']").blur(function() {
            var emailValue = $(this).val();
            if (!isValidEmail(emailValue)) {
                // Выводим сообщение об ошибке
                $("#formResult").html("Пожалуйста, введите корректный email.");

                // Подсвечиваем поле красным
                $(this).css("border-color", "red");
            }
        });


            });
		</script>';

    return $form_html;
}
add_shortcode('macan', 'form_shortcode_function');

function process_macan_form()
{

    // Проверяем, были ли переданы данные из формы
    if (isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['subject'], $_POST['message'])) {
        // Получаем данные из формы
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $subject = sanitize_text_field($_POST['subject']);
        $message = sanitize_textarea_field($_POST['message']);

        // Формируем тело письма
        $mail_body = "First Name: $first_name\n";
        $mail_body .= "Last Name: $last_name\n";
        $mail_body .= "E-mail: $email\n";
        $mail_body .= "Subject: $subject\n";
        $mail_body .= "Message: $message\n";

        // Получаем адрес получателя из опций
        $recipient_email = get_option('macan_email_recipient');

        // Отправляем письмо
        $mail_result = wp_mail($recipient_email, $subject, $mail_body);

        // Проверяем успешность отправки
        if ($mail_result) {
            echo 'Message sent successfully!';

            // Логирование в файл
            $log_file = plugin_dir_path(__FILE__) . 'log.txt';
            $log_entry = date('Y-m-d H:i:s') . " | Email: $email | IP: " . $_SERVER['REMOTE_ADDR'] . "\n";

            if (file_exists($log_file)) {
                // Добавляем запись в существующий файл лога
                file_put_contents($log_file, $log_entry, FILE_APPEND);
            } else {
                // Создаем новый файл лога
                file_put_contents($log_file, $log_entry);
            }
            // Создание контакта в HubSpot
            send_data_to_hubspot($first_name, $email);
        } else {
            echo 'Error sending message.';
        }
    }

    // Прекращаем выполнение скрипта
    wp_die();
}

function send_data_to_hubspot($name, $email)
{
    $hubspot_api_key = get_option('macan_api_key');
    $hubspot_api_url = 'https://api.hubapi.com/crm/v3/objects/contacts';

    $contact_data = array(
        'properties' => array(
            array(
                'property' => 'firstname',
                'value' => $name,
            ),
            array(
                'property' => 'email',
                'value' => $email,
            ),
        ),
    );

    $request_args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $hubspot_api_key,
        ),
        'body' => json_encode($contact_data),
    );

    $response = wp_remote_post($hubspot_api_url, $request_args);

    // Обработка ответа HubSpot API
    if (is_wp_error($response)) {
        // Измененная часть: запись ошибки в файл error.txt в текущей директории
        $error_message = 'HubSpot API Request Error: ' . $response->get_error_message();
        error_log($error_message, 3, __DIR__ . '/error.txt');
    } else {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        // Обработка успешного ответа
        if (isset($result['id'])) {
            // Здесь вы можете добавить код для логирования, если необходимо
        } else {
            // Измененная часть: запись ошибки в файл error.txt в текущей директории
            $error_message = 'HubSpot API Error: ' . print_r($result, true);
            error_log($error_message, 3, __DIR__ . '/error.txt');
        }
    }
}

add_action('wp_ajax_process_macan_form', 'process_macan_form');
add_action('wp_ajax_nopriv_process_macan_form', 'process_macan_form');
