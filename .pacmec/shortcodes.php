<?php
/**
 *
 * @package    PACMEC
 * @category   Shortcodes
 * @copyright  2020-2021 FelipheGomez
 * @author     FelipheGomez <feliphegomez@pm.me>
 * @license    license.txt
 * @version    1.0.1
 */
function pacmec_menu_item_to_li($item1, $classItem=[], $attsItem=[], $tree = true, $enable_tag = true, $enable_icon_m = true, $class_ul_child=[], $classSubitem=[], $attsSubitem=[])
{
  $icon = "";
  if(!empty($item1->icon)) $icon      = \PACMEC\Util\Html::tag("i", "", [$item1->icon]);
  $icon_more = isset($item1->childs) && count($item1->childs)>0 ? \PACMEC\Util\Html::tag("i", "", ["fa fa-caret-down"]) : "";

  $title = ($item1->title);

  $attrs_a = [];
  $attrs_a["href"] = $item1->tag_href;
  if(isset($attsItem['target'])) $attrs_a["target"] = $attsItem['target'];

  if($GLOBALS['PACMEC']['path'] === $item1->tag_href) $classItem[] = ' active';

  if(isset($item1->childs) && count($item1->childs)>0 && $tree == true){
    $subitems = "";
    foreach ($item1->childs as $subitem) {
      $subitems .= \pacmec_menu_item_to_li($subitem, $classSubitem, $attsSubitem, $tree, $enable_tag, $enable_icon_m, $class_ul_child);
    }
    $ul_more = "\n".\PACMEC\Util\Html::tag("ul", $subitems, $class_ul_child);
  } else {
    $ul_more = "";
  }
  $link = \PACMEC\Util\Html::tag("a", "\n{$icon}".($enable_tag==true?" {$title}":"").($enable_icon_m==true?" {$icon_more}":""), $classItem, $attrs_a);
  $item_html = \PACMEC\Util\Html::tag("li", "{$link}{$ul_more}", $classItem, $attsItem);
  return $item_html;
}

/**
*
* Create UL Socials Icons
*
* @param array  $atts
* @param string  $content
*/
function pacmec_social_icons($atts, $content='')
{
  try {
    $repair = shortcode_atts([
      "target" => "_self",
      "enable_tag" => false,
      "class" => [],
      "iconpro" => "",
      "menu_slug" => false,
    ], $atts);
    if($repair['menu_slug'] == false){
      throw new \Exception("Menu no detectado.", 1);
    } else {
      $menu = pacmec_load_menu($repair['menu_slug']);
      if($menu !== false){
        $r_html = "";
        foreach ($menu->items as $key => $item1) {
          $r_html .= \pacmec_menu_item_to_li($item1, $repair['target'], false, $repair['enable_tag']);
        }
        $styles = isset($repair['class']) ? $repair['class'] : ["no-list-style"];
        return \PACMEC\Util\Html::tag("ul", $r_html, $styles);
      } else {
      throw new \Exception("Menu no encontrado.", 1);
      }
    }
  } catch (\Exception $e) {
    return "Ups: pacmec_social_icons: " . $e->getMessage();
  }
}
add_shortcode('pacmec-social-icons', 'pacmec_social_icons');

function pacmec_captcha_widget_forms($atts=[], $content='')
{
  $args = \shortcode_atts([
    "id" => randString(11),
    "name" => randString(11),
    "theme" => 'custom-pacmec',
  ], $atts);
  // pacmec_captcha_widget_html
  //
  return \pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-".$args["id"], $args["name"], $args["theme"]);
}
add_shortcode('pacmec-captcha-widget-forms', 'pacmec_captcha_widget_forms');

function pacmec_form_signin($atts=[], $content='')
{
  global $PACMEC;
  $args = \shortcode_atts([
    'redirect' => false
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $form_slug = "signin-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , \PACMEC\Form\FormType::Horizontal
    , 'Error:'
    , "OK"
    , ['class'=>'pacmec-row']);
  $form->setWidths(12,12);

  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(isset($PACMEC['fullData']["submit-{$form_slug}"]) && isset($PACMEC['fullData']['nick']) && isset($PACMEC['fullData']['hash'])){
            $r_login = $PACMEC['session']->login([
              'nick' => $PACMEC['fullData']['nick'],
              'hash' => $PACMEC['fullData']['hash']
            ]);
            switch ($r_login) {
              case 'no_exist':
                $form->setErrorMessage(__a('signin_r_no_exist'));
                return false;
                break;
              case 'inactive':
                $form->setErrorMessage(__a('signin_r_inactive'));
                return false;
                break;
              case 'error':
                $form->setErrorMessage(__a('signin_r_error'));
                return false;
                break;
              case 'success':
                $form->setSucessMessage(__a('signin_r_success'));
                $url = (isset($PACMEC['fullData']['redirect'])) ? ($PACMEC['fullData']['redirect']) : infosite('siteurl').__url_s("/%pacmec_meaccount%");
                echo "<meta http-equiv=\"refresh\" content=\"0;URL='{$url}'\" />";
                return true;
                break;
              case 'invalid_credentials':
                $form->setErrorMessage(__a('signin_r_invalid_credentials'));
                return false;
                break;
              default:
                $form->setErrorMessage(__a('undefined'));
                return false;
                break;
            }
          } else {
            $form->setErrorMessage(__a('signin_r_invalid_info'));
            return false;
          }
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);

  $form->hidden([
    [
      "name"  => "redirect",
      "value" => ($args['redirect']==false) ? infosite('siteurl').__url_s("/%pacmec_meaccount%") : urldecode($args['redirect'])
    ]
  ]);

  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('nick', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('username')
    , ''
    , ['pacmec-col m12 l12']
  );

  $form->addFieldWithLabel(
    \PACMEC\Form\Password::withNameAndValue('hash', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('hash')
    , ''
    , ['pacmec-col m12 l12']
  );

  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);

  $form->addSubmitButton(__a('signin'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
  ], ['as']);

  $form->Code .= '
    <div class="login-reg-form-meta d-flex align-items-center justify-content-between">
      <a href="'.infosite('siteurl')."/{$GLOBALS['PACMEC']['permanents_links']['%forgotten_password_slug%']}".'" class="forget-pwd mb-3">'.__a('meaccount_forgotten_password').'</a>
    </div>';
  return isGuest() ? \PACMEC\Util\Html::tag('div', $form, []) : '';
}
add_shortcode('pacmec-form-signin', 'pacmec_form_signin');

function pacmec_form_forgotten_password($atts=[], $content='')
{
  global $PACMEC;
  $args = \shortcode_atts([
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $form_slug = "password-forgotten-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Horizontal
    , 'Error:'
    , "OK"
    , ['class'=>'row']);
  $form->setWidths(12,12);
  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('Enlace invalido', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(
        isset($PACMEC['fullData']["ue"])
        && isset($PACMEC['fullData']["kr"])
      ){
        $key = $PACMEC['fullData']["kr"];
        $email = $PACMEC['fullData']["ue"];
        $check_rev = $PACMEC['session']->validateUserDB_recover($key, $email);
        switch ($check_rev) {
          case 'no_exist':
            #$form->setErrorMessage(__a('signin_r_no_exist'));
            return false;
            break;
          case 'inactive':
            #$form->setErrorMessage(__a('signin_r_inactive'));
            return false;
            break;
          case 'error':
            #$form->setErrorMessage(__a('signin_r_error'));
            return false;
            break;
          case $check_rev->id > 0:
            $PACMEC['session']->set('id', $check_rev->id, 'user');
            return true;
            #$form->setErrorMessage(__a('recover_password_r_fail'));
            return false;
            break;
          default:
            #$form->setErrorMessage(__a('recover_password_r_fail'));
            return false;
            break;
         }
      }
      return false;
    })
    , new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['pass1'])
            && isset($PACMEC['fullData']['pass2'])
            && isset($PACMEC['session']->id) && $PACMEC['session']->id > 0
          ){
            if($PACMEC['fullData']['pass1'] !== $PACMEC['fullData']['pass2']){
              $form->setErrorMessage(__a('change_pass_r_error_not_match'));
              return false;
            }
            if($PACMEC['session']->change_pass($PACMEC['fullData']['pass2']) == true){
              $form->setSucessMessage(__a('change_pass_r_success'));
              return true;
            } else {
              $form->setErrorMessage(__a('change_pass_r_error'));
              return false;
            }
          } else {
            $form->setErrorMessage(__a('recover_password_r_fail'));
            return false;
          }
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);
  if(
    isset($PACMEC['fullData']["ue"])
    && isset($PACMEC['fullData']["kr"])
  ){

    $form->addFieldWithLabel(
      \PACMEC\Form\Password::withNameAndValue('pass1', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\MinLengthValidation(4)
      ])
      , __a('pass1')
      , ''
      , ['l12']
    );
      $form->addFieldWithLabel(
        \PACMEC\Form\Password::withNameAndValue('pass2', '', 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          , new \PACMEC\Form\Validation\MinLengthValidation(4)
        ])
        , __a('pass2')
        , ''
        , ['l12']
      );

  } else {
      $form->addFieldWithLabel(
        \PACMEC\Form\Text::withNameAndValue('nick_or_email', '', 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          , new \PACMEC\Form\Validation\MinLengthValidation(4)
        ])
        , __a('nick_or_email')
        , ''
        , ['l12']
      );
  }
  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);
  $form->addSubmitButton(__a('pacmec_forgotten_password'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'btn btn btn-dark btn-hover-primary rounded-0 w-100'
  ]);

  $form->Code .= '
    <div class="login-reg-form-meta d-flex align-items-center justify-content-between">
      <a href="'.infosite('siteurl')."/{$GLOBALS['PACMEC']['permanents_links']['%pacmec_signin%']}".'" class="forget-pwd mb-3">'.__a('signin').'</a>
    </div>';
  return isGuest() ? $form : '';
}
add_shortcode('pacmec-form-forgotten-password', 'pacmec_form_forgotten_password');

function pacmec_form_me_info($atts=[], $content='')
{
  global $PACMEC;
  $args = \shortcode_atts([
    "user_id" => \userID(),
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $_user = (array) $PACMEC['session']->user;
  foreach ($PACMEC['fullData'] as $key => $value) { if(in_array($key, array_keys($_user))){ $PACMEC['session']->user->{$key} = $value; } }
  $ME = ($PACMEC['route']->user);
  $form_slug_saveprofile = "saveprofile";
  $result_saveprofile = \pacmec_captcha_check($form_slug_saveprofile);
  $form = new \PACMEC\Form\Form(
    ''
    // '#pacmec_form_me_info'
    , 'POST'
    , PACMEC\Form\FormType::Horizontal
    , "Revisa los campos"
    , "OK"
    , ['class'=>'row']);
  $form->setWidths(12,12);
  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation(__a($result_saveprofile), function () use ($PACMEC, $form_slug_saveprofile, $result_saveprofile, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_saveprofile !== 'captcha_disabled')) return false;
      switch ($result_saveprofile) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if($PACMEC['session']->save()==true){
            $result_saveprofile = __a("saveprofile_r_success");
            $form->setSucessMessage($result_saveprofile);
            return true;
          } else {
            $result_saveprofile = __a("saveprofile_r_error");
            return false;
          }
          break;
        default:
          return false;
          break;
      }
      return true;
    })
  ]);
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('names', $ME->user->names, 100, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(2)
    ])
    , __a('names')
    , ''
    , ['l6 ']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('surname', $ME->user->surname, 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(3)
    ])
    , __a('surname')
    , ''
    , ['l6']
  );
  $options = [];
  foreach (type_options('identifications') as $a) { $options["{$a->id}"] = $a->name; }
  $form->addFieldWithLabel(
    \PACMEC\Form\Select::withNameAndOptions('identification_type', $options, $ME->user->identification_type, array_keys($options), ['class'=>'pacmec-select nice-select wide'])
    , __a('identification_type')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('identification_number', $ME->user->identification_number, 30, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ], [])
    , __a('identification_number')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('phone', $ME->user->phone, 25, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
    ], ['data-mask'=>"(00) 0000-0000"])
    , __a('phone')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('mobile', $ME->user->mobile, 25, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(9)
    ], ['data-mask'=>"(0#) 000-0000000"])
    , __a('mobile')
    , ''
    , ['l6']
  );
  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug_saveprofile, 'custom-pacmec'), ['single-input-item mb-3']);
  $form->addSubmitButton(__a('save_changes_btn'), [
    'name'=>"submit-{$form_slug_saveprofile}",
    "class" => 'btn btn-sm btn-outline-dark btn-hover-primary w-100'
  ]);
  return $form;
}
add_shortcode('pacmec-form-me-info', 'pacmec_form_me_info');

function pacmec_form_register($atts=[], $content='')
{
  global $PACMEC;
  $args = \shortcode_atts([
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $form_slug = "createprofile";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    ''
    // '#pacmec_form_me_info'
    , 'POST'
    , PACMEC\Form\FormType::Horizontal
    , "Revisa los campos"
    , "OK"
    , ['class'=>'row']);
  $form->setWidths(12,12);
  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['pass1']) && !empty($PACMEC['fullData']['pass1'])
            && isset($PACMEC['fullData']['pass2']) && !empty($PACMEC['fullData']['pass2'])
            && isset($PACMEC['fullData']['username']) && !empty($PACMEC['fullData']['username'])
            && isset($PACMEC['fullData']['email']) && !empty($PACMEC['fullData']['email'])
            && isset($PACMEC['fullData']['names']) && !empty($PACMEC['fullData']['names'])
            && isset($PACMEC['fullData']['surname']) && !empty($PACMEC['fullData']['surname'])
            && isset($PACMEC['fullData']['identification_type']) && !empty($PACMEC['fullData']['identification_type'])
            && isset($PACMEC['fullData']['identification_number']) && !empty($PACMEC['fullData']['identification_number'])
            && isset($PACMEC['fullData']['phone']) && !empty($PACMEC['fullData']['phone'])
            && isset($PACMEC['fullData']['mobile']) && !empty($PACMEC['fullData']['mobile'])
          ){
            if($PACMEC['fullData']['pass1'] !== $PACMEC['fullData']['pass2']){
              $form->setErrorMessage(__a('change_pass_r_error_not_match'));
              return false;
            }
            $user = new \PACMEC\System\Users();
            $user->set_all($PACMEC['fullData']);
            $user->permissions = infosite('register_group_def');
            $user->hash   = password_hash($PACMEC['fullData']['pass2'], PASSWORD_DEFAULT);
            $user->status = 1;
            // CREA USUARIO ACÁ
            $result_user_creation = $user->create();
            if($result_user_creation !== false && $result_user_creation>0){
              if(infosite('register_email_welcome') !== false){
                // creado correo de bienvenida.
                $mail = new \PACMEC\System\EmailsTemplates((object) ['template_slug'=>infosite('register_email_welcome')]);
                if($mail->isValid()){
                  $mail->set_autot([
                    '%sitelogo%',
                    '%sitename%',
                    '%PreviewText%',
                    '%username%',
                    '%names%',
                    '%surname%',
                    '%password%',
                    '%email%',
                    '%siteurl%',
                    '%email_title%',
                    '%register_email_body%',
                    '%register_from_title_subject%',
                  ], [
                    infosite('siteurl').infosite('sitelogo'),
                    infosite('sitename'),
                    infosite('sitedescr'),
                    $user->username,
                    $user->names,
                    $user->surname,
                    $PACMEC['fullData']['pass2'],
                    $user->email,
                    infosite('siteurl').infosite('homeurl'),
                    sprintf(__a('register_email_title'), "{$user->names} {$user->surname}"),
                    (__a('register_email_body')),
                    sprintf(__a('register_from_title_subject_start'), "{$user->username}"),
                  ]);
                  $result_send = $mail->send(sprintf(__a('register_from_title_subject_start'), infosite('sitename')), $user->email, "{$user->names} {$user->surname}");
                }
              }
              $r_login = $PACMEC['session']->login([
                'nick' => $PACMEC['fullData']['username'],
                'hash' => $PACMEC['fullData']['pass2']
              ]);
              switch ($r_login) {
                case 'no_exist':
                  $form->setErrorMessage(__a('signin_r_no_exist'));
                  return false;
                  break;
                case 'inactive':
                  $form->setErrorMessage(__a('signin_r_inactive'));
                  return false;
                  break;
                case 'error':
                  $form->setErrorMessage(__a('signin_r_error'));
                  return false;
                  break;
                case 'success':
                  $form->setSucessMessage(__a('signin_r_success'));
                  $url = (isset($PACMEC['fullData']['redirect'])) ? ($PACMEC['fullData']['redirect']) : infosite('siteurl').__url_s("/%pacmec_meaccount%");
                  echo "<meta http-equiv=\"refresh\" content=\"0;URL='{$url}'\" />";
                  return true;
                  break;
                case 'invalid_credentials':
                  $form->setErrorMessage(__a('signin_r_invalid_credentials'));
                  return false;
                  break;
                default:
                  $form->setSucessMessage(__a('register_r_success'));
                  return true;
                  break;
              }
            }

            $form->setErrorMessage(__a('register_r_fail'));
            return false;
          } else {
            $form->setErrorMessage(__a('change_pass_r_invalid_credentials'));
            return false;
          }
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('username', '', 17, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
      , new \PACMEC\Form\Validation\LambdaValidation(__a('register_nick_exist'), function () use ($PACMEC, $form_slug, $form) {
        if(
          isset($PACMEC['fullData']["submit-{$form_slug}"])
          && isset($PACMEC['fullData']['username'])
        ){
          $model = new \PACMEC\System\Users((object) ['user_nick'=>$PACMEC['fullData']['username']]);
          if($model->isValid()) return false;
          else return true;;
        }
      })
    ], [])
    , __a('username')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('email', '', '', [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\LambdaValidation(__a('register_email_exist'), function () use ($PACMEC, $form_slug, $form) {
        if(
          isset($PACMEC['fullData']["submit-{$form_slug}"])
          && isset($PACMEC['fullData']['email'])
        ){
          $model = new \PACMEC\System\Users((object) ['user_email'=>$PACMEC['fullData']['email']]);
          if($model->isValid()) return false;
          else return true;;
        }
      })
    ], ['type'=>"email"])
    , __a('email')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('names', '', 100, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(2)
    ])
    , __a('names')
    , ''
    , ['l6 ']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('surname', '', 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(2)
    ])
    , __a('surname')
    , ''
    , ['l6']
  );
  $options = [];
  foreach (type_options('identifications') as $a) { $options["{$a->id}"] = $a->name; }
  $form->addFieldWithLabel(
    \PACMEC\Form\Select::withNameAndOptions('identification_type', $options, '', array_keys($options), ['class'=>'nice-select wide'])
    , __a('identification_type')
    , ''
    , ['l8']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('identification_number', '', 30, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ], [])
    , __a('identification_number')
    , ''
    , ['l4']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('phone', '', 25, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
    ], ['data-mask'=>"(00) 0000-0000"])
    , __a('phone')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('mobile', '', 25, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(9)
    ], ['data-mask'=>"(0#) 000-0000000"])
    , __a('mobile')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Password::withNameAndValue('pass1', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('pass1')
    , ''
    , ['l6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Password::withNameAndValue('pass2', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('pass2')
    , ''
    , ['l6']
  );

  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);

  $form->addSubmitButton(__a('register_btn'), [
    'name'=>"submit-{$form_slug}",
    // "class" => 'btn btn-sm btn-outline-dark btn-hover-primary'
    #"class" => 'btn btn-sm btn-dark btn-hover-primary'
    "class" => 'btn btn btn-dark btn-hover-primary rounded-0 w-100'
  ]);
  return $form;
}
add_shortcode('pacmec-form-register', 'pacmec_form_register');

function pacmec_form_me_change_pass($atts=[], $content='')
{
  global $PACMEC;
  $args = \shortcode_atts([
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $form_slug = "change_pass-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    // infosite('siteurl')."/{$GLOBALS['PACMEC']['permanents_links']['%pacmec_signin%']}"
    ''
    // '#pacmec_form_me_info'
    , 'POST'
    , PACMEC\Form\FormType::Horizontal
    , 'Error:'
    , "OK"
    , ['class'=>'row']);
  $form->setWidths(12,12);

  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['current_pass'])
            && isset($PACMEC['fullData']['pass1'])
            && isset($PACMEC['fullData']['pass2'])
          ){
            if($PACMEC['fullData']['pass1'] !== $PACMEC['fullData']['pass2']){
              $form->setErrorMessage(__a('change_pass_r_error_not_match'));
              return false;
            } else {
              if ($PACMEC['session']->check_password($GLOBALS['PACMEC']['fullData']['current_pass']) == true) {
                if($PACMEC['session']->change_pass($PACMEC['fullData']['pass2']) == true){
                  $form->setSucessMessage(__a('change_pass_r_success'));
                  return true;
                } else {
                  $form->setErrorMessage(__a('change_pass_r_error'));
                  return false;
                }
              } else {
                $form->setErrorMessage(__a('change_pass_r_auth_invalid'));
                return false;
              }
            }
          } else {
            $form->setErrorMessage(__a('change_pass_r_invalid_credentials'));
            return false;
          }
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);

  $form->addFieldWithLabel(
    \PACMEC\Form\Password::withNameAndValue('current_pass', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('current_pass')
    , ''
    , ['l12']
  );

  $form->addFieldWithLabel(
    \PACMEC\Form\Password::withNameAndValue('pass1', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('pass1')
    , ''
    , ['l6']
  );

  $form->addFieldWithLabel(
    \PACMEC\Form\Password::withNameAndValue('pass2', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('pass2')
    , ''
    , ['l6']
  );

  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);

  $form->addSubmitButton(__a('save_changes_btn'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'btn btn-sm btn-outline-dark btn-hover-primary'
  ]);

  return $form;
}
add_shortcode('pacmec-form-me-change-pass', 'pacmec_form_me_change_pass');

function pacmec_form_me_change_access($atts=[], $content='')
{
  global $PACMEC;
  $_user = (array) $PACMEC['session']->user;
  foreach ($PACMEC['fullData'] as $key => $value) { if(in_array($key, array_keys($_user))){ $PACMEC['session']->user->{$key} = $value; } }
  $ME = ($PACMEC['route']->user);
  $args = \shortcode_atts([
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $form_slug = "change_access-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    // infosite('siteurl')."/{$GLOBALS['PACMEC']['permanents_links']['%pacmec_signin%']}"
    ''
    // '#pacmec_form_me_info'
    , 'POST'
    , PACMEC\Form\FormType::Horizontal
    , 'Error:'
    , "OK"
    , ['class'=>'row']);
  $form->setWidths(12,12);

  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['current_pass'])
          ){
            if ($PACMEC['session']->check_password($GLOBALS['PACMEC']['fullData']['current_pass']) == true) {
              if($PACMEC['session']->save_info_access()==true){
                $form->setSucessMessage(__a('saveaccess_r_success'));
                return true;
              } else {
                $form->setErrorMessage(__a('saveaccess_r_error'));
                return false;
              }
            } else {
              $form->setErrorMessage(__a('change_pass_r_invalid_credentials'));
              return false;
            }
          }
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);

  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('username', $ME->user->username, 17, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
      , new \PACMEC\Form\Validation\LambdaValidation(__a('register_nick_exist'), function () use ($PACMEC, $form_slug, $form) {
        if(
          isset($PACMEC['fullData']["submit-{$form_slug}"])
          && isset($PACMEC['fullData']['username'])
        ){
          $model = new \PACMEC\System\Users((object) ['user_nick'=>$PACMEC['fullData']['username']]);
          if($model->isValid()) return false;
          else return true;;
        }
      })
    ], [])
    , __a('username')
    , ''
    , ['l6']
  );

  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('email', $ME->user->email, '', [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
    ], ['type'=>"email"])
    , __a('email')
    , ''
    , ['l6']
  );

  $form->addFieldWithLabel(
    \PACMEC\Form\Password::withNameAndValue('current_pass', '', 32, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(4)
    ])
    , __a('current_pass')
    , ''
    , ['l12']
  );

  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);

  $form->addSubmitButton(__a('save_changes_btn'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'btn btn-sm btn-outline-dark btn-hover-primary'
  ]);

  return $form;
}
add_shortcode('pacmec-form-me-change-access', 'pacmec_form_me_change_access');

function pacmec_me_welcome_small($atts=[], $content='')
{
  global $PACMEC;
  $ME = ($PACMEC['session']);
  return
  \PACMEC\Util\Html::tag('div',
    \PACMEC\Util\Html::tag('div',
      \PACMEC\Util\Html::tag('h1', __a('hello').', '. \PACMEC\Util\Html::tag('strong', $ME->user->username, [], []), [], [])
    , ['welcome'], [])
    . \PACMEC\Util\Html::tag('p', __a('me_account_descr'), ['mb-0'], [])
  , [], []);

}
add_shortcode('pacmec-me-welcome-small', 'pacmec_me_welcome_small');

function pacmec_me_welcome_medium($atts=[], $content='')
{
  global $PACMEC;
  $ME = ($PACMEC['route']->user);
  return
  \PACMEC\Util\Html::tag('div'
  , \PACMEC\Util\Html::tag('div',
      \PACMEC\Util\Html::tag('h3', __a('me_account'), ['title'], [])
      . \PACMEC\Util\Html::tag('div',
        \PACMEC\Util\Html::tag('p', __a('hello').', '. \PACMEC\Util\Html::tag('strong', $ME->user->username, [], []), [], [])
      , ['welcome'], [])
      . \PACMEC\Util\Html::tag('p', __a('me_account_descr'), ['mb-0'], [])
    , ['myaccount-content'], [])
  , ['tab-pane'], ['id'=>"dashboad", 'role'=>"tabpanel"]);
}
add_shortcode('pacmec-me-welcome-medium', 'pacmec_me_welcome_medium');

function pacmec_me_orders_table($atts=[], $content='')
{
  return \PACMEC\System\Orders::table_list_html(\PACMEC\System\Orders::get_all_by_user_id());
}
add_shortcode('pacmec-me-orders-table', 'pacmec_me_orders_table');

function pacmec_me_notifications_table($atts=[], $content='')
{
  global $PACMEC;
  return \PACMEC\System\Notifications::table_list_html(\PACMEC\System\Notifications::get_all_by_user_id(null, true));
}
add_shortcode('pacmec-me-notifications-table', 'pacmec_me_notifications_table');

function pacmec_me_payments_table($atts=[], $content='')
{
  return \PACMEC\System\Payments::table_list_html(\PACMEC\System\Payments::get_all_by_user_id());
}
add_shortcode('pacmec-me-payments-table', 'pacmec_me_payments_table');

function pacmec_me_addresses_table($atts=[], $content='')
{
  global $PACMEC;
  $add = "";
  if(isset($PACMEC['fullData']['remove_id']) && !empty($PACMEC['fullData']['remove_id'])) {
    $result_remove = \PACMEC\System\GeoAddresses::remove_from_user($PACMEC['fullData']['remove_id']);
    if($result_remove!==false&&$result_remove>0){
      $add .= \PACMEC\Util\Html::tag('div', __a('remove_address_success'), ['alert alert-success']);
    } else {
      $add .= \PACMEC\Util\Html::tag('div', __a('remove_address_fail'), ['alert alert-success']);
    }
  }
  return $add.\PACMEC\System\GeoAddresses::table_list_html(\PACMEC\System\GeoAddresses::get_all_by_user_id());
}
add_shortcode('pacmec-me-addresses-table', 'pacmec_me_addresses_table');

function pacmec_order_apply_coupon($atts=[], $content='')
{
  global $PACMEC;
  $args = \shortcode_atts([
    "order_id"=>false
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $form_slug = "order-apply-cupon-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  if($args['order_id'] == false) return "";
  $order = new \PACMEC\System\Orders((object) ['order_id'=>$args['order_id']]);
  if(!$order->isValid()) return (__a('order_not_match'));
  if($order->pay_enabled==false) return __a('order_payment_not_available');

  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Horizontal
    , 'Error:'
    , "OK"
    , ['class'=>'form row']);
  $form->setWidths(12,12);
  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $order) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['coupon_code'])
          ){
            $coupon = new \PACMEC\System\Coupons((object) [
              "coupon_code" => $PACMEC['fullData']['coupon_code']
            ]);

            if($coupon->isValid() && $PACMEC['fullData']['coupon_code'] === $coupon->code){
              $checker_gbl = \decrypt($coupon->hash, '*');
              $checker_host = \decrypt($coupon->hash, 'fym.managertechnology.com.co');
              $code_v1 = \encrypt($PACMEC['fullData']['coupon_code'], $PACMEC['host']);
              if($PACMEC['fullData']['coupon_code'] === $checker_gbl || $PACMEC['fullData']['coupon_code'] === $checker_host){
                if($coupon->redeemed_date !== null){
                  $form->setErrorMessage(__a('coupon_already_redeemed'));
                  return false;
                }
                if($coupon->expiration_date !== null){
                  $datetime1 = date_create('now');
                  $datetime2 = date_create($coupon->expiration_date);
                  if($datetime1 >= $datetime2){
                    $form->setErrorMessage(__a('coupon_expired'));
                    return false;
                  }
                }
                $current_total = (float) $order->total;
                $coupon_total  = (float) $coupon->amount;
                $new_total  = $current_total - $coupon_total;
                $coupon_item = new \PACMEC\System\OrdersItems();
                $coupon_item->order_id = $order->getId();
                $coupon_item->type = "coupon";
                $coupon_item->ref = $coupon->getId();
                $coupon_item->discount_amount = $coupon->amount;
                $coupon_create_result = $coupon_item->create();
                if($coupon_create_result !== false && $coupon_create_result>0){
                  $cou_pay = $coupon->redeemed();
                  if($cou_pay == true){
                    $form->setSucessMessage(__a('coupon_applied'));
                    echo "<meta http-equiv=\"refresh\" content=\"0\" />";
                    return true;
                  }
                }
                $form->setErrorMessage(__a('coupon_error_applied'));
                return false;
              } else {
                $form->setErrorMessage(__a('coupon_not_match_host'));
                return false;
              }
            } else {
              $form->setErrorMessage(__a('coupon_not_match'));
              return false;
            }
            return false;
          }
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('coupon_code', '', 25, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
    ])
    , __a('coupon_code')
    , ''
    , ['l7 s12']
  );

  $form->Code .= \PACMEC\Util\Html::tag('div',
  \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3'])
  , ['l7 s12']);

  $form->Code .= '<div class="l12 s12">';
  $form->addSubmitButton(__a('coupon_apply_btn'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'btn btn-sm btn-dark btn-hover-primary'
  ]);
  $form->Code .= '</div>';
  return $form;
}
add_shortcode('pacmec-order-apply-coupon', 'pacmec_order_apply_coupon');

function pacmec_me_add_address($atts=[], $content='')
{
  global $PACMEC;
  $form_slug = "site-address-add-form-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Normal
    , 'Error:'
    , "OK"
    , ['class'=>'checkbox-form row', 'id'=>$form_slug]);
  $form->setWidths(12,12);

  $form->Code .= '<style>
  .select2-container { width:100% !important; }
  .select2-container--default .select2-selection--single { height: 42px; }
  .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:42px; }
  </style>';

  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']['country']) && !empty($PACMEC['fullData']['country'])
            && isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['city']) && !empty($PACMEC['fullData']['city'])
            && isset($PACMEC['fullData']['main_road']) && !empty($PACMEC['fullData']['main_road'])
            && isset($PACMEC['fullData']['main_road_text']) && !empty($PACMEC['fullData']['main_road_text'])
            && isset($PACMEC['fullData']['minor_road']) && !empty($PACMEC['fullData']['minor_road'])
            && isset($PACMEC['fullData']['number']) && !empty($PACMEC['fullData']['number'])
            && isset($PACMEC['fullData']['extra']) && !empty($PACMEC['fullData']['extra'])
            && isset($PACMEC['fullData']['extra_text']) && !empty($PACMEC['fullData']['extra_text'])
          ){
            $code_country = null;
            if(isset($PACMEC['fullData']['country']) && !empty($PACMEC['fullData']['country'])){
              foreach ($PACMEC['geo']['countries'] as $i => $c){
                if($c->id == $PACMEC['fullData']['country']) {
                  $code_country = $c;
                  break;
                }
              }
            }
            $obj_city = null;
            foreach ($PACMEC['geo']['cities'][$code_country->code] as $i => $c){
              if($c->id == $PACMEC['fullData']['city']) {
                $obj_city = $c;
                break;
              }
            }
            $obj_main_road = null;
            foreach (type_options('geo_types_vias') as $option):
              if($PACMEC['fullData']['main_road'] == $option->id) {
                $obj_main_road = $option;
                break;
              }
            endforeach;
            $obj_extra = null;
            foreach (type_options('geo_extra') as $option):
              if($PACMEC['fullData']['extra'] == $option->id) {
                $obj_extra = $option;
                break;
              }
            endforeach;
            $resutl_create = new \PACMEC\System\GeoAddresses();
            $resutl_create->country = $PACMEC['fullData']['country'];
            $resutl_create->city = $PACMEC['fullData']['city'];
            $resutl_create->main_road = $PACMEC['fullData']['main_road'];
            $resutl_create->main_road_text = $PACMEC['fullData']['main_road_text'];
            $resutl_create->minor_road = $PACMEC['fullData']['minor_road'];
            $resutl_create->number = $PACMEC['fullData']['number'];
            $resutl_create->extra = $PACMEC['fullData']['extra'];
            $resutl_create->extra_text = $PACMEC['fullData']['extra_text'];
            $resutl_create->details = isset($PACMEC['fullData']['details']) ? $PACMEC['fullData']['details'] : '';
            $resutl_create->mini = "{$obj_main_road->code} {$resutl_create->main_road_text} "
              . "# {$resutl_create->minor_road}-{$resutl_create->number} {$obj_extra->code} {$resutl_create->extra_text}"
              . ((!empty($resutl_create->details)) ? " ({$resutl_create->details})" : "")
              . ", {$obj_city->name}, {$code_country->name}";
            $resutl_create->full = "{$obj_main_road->name} {$resutl_create->main_road_text} "
              . "# {$resutl_create->minor_road}-{$resutl_create->number} {$obj_extra->name} {$resutl_create->extra_text}"
              . ((!empty($resutl_create->details)) ? " ({$resutl_create->details})" : "")
              . ", {$obj_city->name}, {$code_country->name}";
            $result_create = $resutl_create->create();
            if($resutl_create->id>0){
              $result_user = $resutl_create->add_in_user();
              if($result_user!==false&&$result_user>0){
                $url = infosite('siteurl').__url_s("/%pacmec_meaccount%?tab=me_addresses");
                echo "<meta http-equiv=\"refresh\" content=\"0;URL='{$url}'\" />";
                $form->setSucessMessage(__a('add_address_success'));
                return true;
              } else {
                $form->setErrorMessage(__a('add_address_fail'));
                return false;
              }
              $form->setErrorMessage(__a('add_address_fail'));
              return false;
            } else {
              $form->setErrorMessage(__a('add_address_fail'));
              return false;
            }
          }
          $form->setErrorMessage(__a('add_address_fail'));
          return false;
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);
  \pacmec_add_part_form_new_address($form);
  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);
  $form->addSubmitButton(__a('save_changes_btn'), [
  'name'=>"submit-{$form_slug}",
  "class" => 'btn btn-dark btn-hover-primary rounded-0 w-100'
  //"class" => 'btn btn-sm btn-dark btn-hover-primary'
  ]);
  $form->Code .= '
  <script>
  function selectCountry(){
    Þ(document).ready(function() {
      Þ(".js-item-basic-single").select2({
        placeholder: "'.__a('select_an_option').'",
      });
    });
    Þ(\'select[name="country"]\').on(\'change\', function() {
      document.getElementById("'.$form_slug.'").submit();
    });
  }
  window.addEventListener(\'load\', selectCountry);
  </script>';
  return $form;
}
add_shortcode('pacmec-me-add-address', 'pacmec_me_add_address');

function pacmec_order_add_address($atts=[], $content='')
{
  global $PACMEC;
  $args = \shortcode_atts([
    "order_id"=>false
  ], $atts);
  $form_slug = "back-order-address-add-form-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Normal
    , 'Error:'
    , "OK"
    , ['class'=>'checkbox-form row', 'id'=>$form_slug]);
  $form->setWidths(12,12);
  if($args['order_id'] !== false){
    $form->Code .= '<style>
    .select2-container { width:100% !important; }
    .select2-container--default .select2-selection--single { height: 42px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:42px; }
    </style>';

    $form->setGlobalValidations([
      new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
        if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
        switch ($result_captcha) {
          case 'captcha_r_success':
          case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']['country']) && !empty($PACMEC['fullData']['country'])
            && isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['city']) && !empty($PACMEC['fullData']['city'])
            && isset($PACMEC['fullData']['main_road']) && !empty($PACMEC['fullData']['main_road'])
            && isset($PACMEC['fullData']['main_road_text']) && !empty($PACMEC['fullData']['main_road_text'])
            && isset($PACMEC['fullData']['minor_road']) && !empty($PACMEC['fullData']['minor_road'])
            && isset($PACMEC['fullData']['number']) && !empty($PACMEC['fullData']['number'])
            && isset($PACMEC['fullData']['extra']) && !empty($PACMEC['fullData']['extra'])
            && isset($PACMEC['fullData']['extra_text']) && !empty($PACMEC['fullData']['extra_text'])
          ){
            $code_country = null;
            if(isset($PACMEC['fullData']['country']) && !empty($PACMEC['fullData']['country'])){
              foreach ($PACMEC['geo']['countries'] as $i => $c){
                if($c->id == $PACMEC['fullData']['country']) {
                  $code_country = $c;
                  break;
                }
              }
            }
            $obj_city = null;
            foreach ($PACMEC['geo']['cities'][$code_country->code] as $i => $c){
              if($c->id == $PACMEC['fullData']['city']) {
                $obj_city = $c;
                break;
              }
            }
            $obj_main_road = null;
            foreach (type_options('geo_types_vias') as $option):
              if($PACMEC['fullData']['main_road'] == $option->id) {
                $obj_main_road = $option;
                break;
              }
            endforeach;
            $obj_extra = null;
            foreach (type_options('geo_extra') as $option):
              if($PACMEC['fullData']['extra'] == $option->id) {
                $obj_extra = $option;
                break;
              }
              endforeach;
              $resutl_create = new \PACMEC\System\GeoAddresses();
              $resutl_create->country = $PACMEC['fullData']['country'];
              $resutl_create->city = $PACMEC['fullData']['city'];
              $resutl_create->main_road = $PACMEC['fullData']['main_road'];
              $resutl_create->main_road_text = $PACMEC['fullData']['main_road_text'];
              $resutl_create->minor_road = $PACMEC['fullData']['minor_road'];
              $resutl_create->number = $PACMEC['fullData']['number'];
              $resutl_create->extra = $PACMEC['fullData']['extra'];
              $resutl_create->extra_text = $PACMEC['fullData']['extra_text'];
              $resutl_create->details = isset($PACMEC['fullData']['details']) ? $PACMEC['fullData']['details'] : '';
              $resutl_create->mini = "{$obj_main_road->code} {$resutl_create->main_road_text} "
              . "# {$resutl_create->minor_road}-{$resutl_create->number} {$obj_extra->code} {$resutl_create->extra_text}"
              . ((!empty($resutl_create->details)) ? " ({$resutl_create->details})" : "")
              . ", {$obj_city->name}, {$code_country->name}";
              $resutl_create->full = "{$obj_main_road->name} {$resutl_create->main_road_text} "
              . "# {$resutl_create->minor_road}-{$resutl_create->number} {$obj_extra->name} {$resutl_create->extra_text}"
              . ((!empty($resutl_create->details)) ? " ({$resutl_create->details})" : "")
              . ", {$obj_city->name}, {$code_country->name}";
              $result_create = $resutl_create->create();
              if($resutl_create->id>0){
                $result_user = $resutl_create->add_in_user();
                if($result_user!==false&&$result_user>0){
                  $url = infosite('siteurl').__url_s("/%pacmec_meaccount%?tab=me_addresses");
                  echo "<meta http-equiv=\"refresh\" content=\"0;URL='{$url}'\" />";
                  $form->setSucessMessage(__a('add_address_success'));
                  return true;
                } else {
                  $form->setErrorMessage(__a('add_address_fail'));
                  return false;
                }
                $form->setErrorMessage(__a('add_address_fail'));
                return false;
              } else {
                $form->setErrorMessage(__a('add_address_fail'));
                return false;
              }
            }
            $form->setErrorMessage(__a('add_address_fail'));
            return false;
            break;
            default:
            $form->setErrorMessage(__a($result_captcha));
            return false;
            break;
          }
          return true;
        })
    ]);
    \pacmec_add_part_form_new_address($form);
    $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);
    $form->addSubmitButton(__a('save_changes_btn'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'btn btn-dark btn-hover-primary rounded-0 w-100'
    //"class" => 'btn btn-sm btn-dark btn-hover-primary'
    ]);
    $form->Code .= '
    <script>
      function selectCountry(){
        Þ(document).ready(function() {
          Þ(".js-item-basic-single").select2({
            placeholder: "'.__a('select_an_option').'",
          });
        });
        Þ(\'select[name="country"]\').on(\'change\', function() {
          document.getElementById("'.$form_slug.'").submit();
        });
      }
      window.addEventListener(\'load\', selectCountry);
    </script>';
  }
  return $form;
}
add_shortcode('pacmec-order-add-address', 'pacmec_order_add_address');

function pacmec_add_part_form_new_address($form)
{
  global $PACMEC;
  $code_country = null;
  if(isset($PACMEC['fullData']['country']) && !empty($PACMEC['fullData']['country'])){
    foreach ($PACMEC['geo']['countries'] as $i => $c){
      if($c->id == $PACMEC['fullData']['country']) {
        $code_country = $c;
        break;
      }
    }
  }
  $html = "";
  $options_countries = [''=>__a('select_an_option')];
  $options_countries_k = [];
  foreach ($PACMEC['geo']['countries'] as $i => $country) $options_countries_k["{$country->id}"] = $options_countries["{$country->id}"] = "{$country->name} [{$country->code}]";
  $options_cities = [''=>__a('select_an_option')];
  $options_cities_k = [];
  if ($code_country!==null) foreach ($PACMEC['geo']['cities'][$code_country->code] as $i => $city) $options_cities_k[$city->id] = $options_cities[$city->id] = $city->name;
  $options_geo_types_vias = [''=>__a('select_an_option')];
  $options_geo_types_vias_k = [];
  foreach (type_options('geo_types_vias') as $option) $options_geo_types_vias_k[$option->id] = $options_geo_types_vias[$option->id] = "{$option->name} [{$option->code}]";
  $options_geo_extra = [''=>__a('select_an_option')];
  $options_geo_extra_k = [];
  foreach (type_options('geo_extra') as $option):
    $options_geo_extra_k[$option->id] = $options_geo_extra[$option->id] = "{$option->name} [{$option->code}]";
  endforeach;
  $form->addFieldWithLabel(
    new \PACMEC\Form\Select(
      $options_geo_types_vias
      , ""
      , [
        'class'  => 'js-item-basic-single'
        , 'name' => 'main_road'
        , 'id' => 'main_road'
        , 'required' => ''
      ]
      , [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\InListValidation(__a('required_field'), array_keys($options_geo_types_vias_k))
      ]
    )
    , __a('address') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m4 checkout-form-list']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('main_road_text',
      ""
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ])
    , ''
    , ''
    , ['pacmec-col m2']
  );
  $form->Code .= \PACMEC\Util\Html::tag('div', '<br>#', ['pacmec-col m1']);
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('minor_road',
      ""
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ])
    , ''
    , ''
    , ['pacmec-col m2']
  );
  $form->Code .= \PACMEC\Util\Html::tag('div', '<br>-', ['pacmec-col m1']);
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('number',
      ""
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ])
    , ''
    , ''
    , ['pacmec-col m2']
  );
  $form->Code .= "<div class=\"pacmec-col m12\"><br></div>";
  $form->addFieldWithLabel(
    new \PACMEC\Form\Select(
      $options_geo_extra
      , ""
      , [
        'class'  => 'js-item-basic-single'
        , 'name' => 'extra'
        , 'id' => 'extra'
        , 'required' => ''
      ]
      , [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\InListValidation(__a('required_field'), array_keys($options_geo_extra_k))
      ]
    )
    , '&nbsp;'
    , ''
    , ['pacmec-col m4 checkout-form-list']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('extra_text',
      ""
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ])
    , ''
    , ''
    , ['pacmec-col m4']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('details',
      ""
    , 254, [
    ])
    , ''
    , ''
    , ['pacmec-col m4']
  );
  $form->Code .= "<div class=\"pacmec-col m12\"><br></div>";
  $form->addFieldWithLabel(
    new \PACMEC\Form\Select(
      $options_countries
      , ""
      , [
        'class'  => 'js-item-basic-single'
        , 'name' => 'country'
        , 'id' => 'country'
        , 'required' => ''
      ]
      , [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\InListValidation(__a('required_field'), array_keys($options_countries_k))
      ]
    )
    , __a('country') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 checkout-form-list']
  );
  $form->addFieldWithLabel(
    new \PACMEC\Form\Select(
      $options_cities
      , ""
      , [
        'class'  => 'js-item-basic-single'
        , 'name' => 'city'
        , 'id' => 'city'
        , 'required' => ''
      ]
      , [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\InListValidation(__a('required_field'), array_keys($options_cities_k))
      ]
    )
    , __a('city') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 checkout-form-list']
  );
  return $form;
}

function pacmec_form_create_order_site($atts=[], $content='')
{
  global $PACMEC;
  $form_slug = "site-order-create-form-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Normal
    , 'Error:'
    , "OK"
    , ['class'=>'pacmec-row', 'id'=>$form_slug]);
  $form->setWidths(12,12);
  $form->Code .= '<style>
  .select2-container { width:100% !important; }
  .select2-container--default .select2-selection--single { height: 42px; }
  .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:42px; }
  </style>';
  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(count($PACMEC['session']->shopping_cart)<=0) {
            $form->setErrorMessage(__a('order_in_black'));
            return false;
          }
          $address_id = 0;
          if (isset($PACMEC['fullData']['address']) && !empty($PACMEC['fullData']['address']) && isset($PACMEC['fullData']["submit-{$form_slug}"])){
            $resutl_create = new \PACMEC\System\GeoAddresses((object) ['address_id'=>$PACMEC['fullData']['address']]);
            $address_id = $resutl_create!==false && $resutl_create->isValid() ? $resutl_create->id : 0;
          }
          if(
            isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['country']) && !empty($PACMEC['fullData']['country'])
            && isset($PACMEC['fullData']['city']) && !empty($PACMEC['fullData']['city'])
            && isset($PACMEC['fullData']['main_road']) && !empty($PACMEC['fullData']['main_road'])
            && isset($PACMEC['fullData']['main_road_text']) && !empty($PACMEC['fullData']['main_road_text'])
            && isset($PACMEC['fullData']['minor_road']) && !empty($PACMEC['fullData']['minor_road'])
            && isset($PACMEC['fullData']['number']) && !empty($PACMEC['fullData']['number'])
            && isset($PACMEC['fullData']['extra']) && !empty($PACMEC['fullData']['extra'])
            && isset($PACMEC['fullData']['extra_text']) && !empty($PACMEC['fullData']['extra_text'])
          ){
            $code_country = null;
            if(isset($PACMEC['fullData']['country']) && !empty($PACMEC['fullData']['country'])){
              foreach ($PACMEC['geo']['countries'] as $i => $c){
                if($c->id == $PACMEC['fullData']['country']) {
                  $code_country = $c;
                  break;
                }
              }
            }
            $obj_city = null;
            foreach ($PACMEC['geo']['cities'][$code_country->code] as $i => $c){
              if($c->id == $PACMEC['fullData']['city']) {
                $obj_city = $c;
                break;
              }
            }
            $obj_main_road = null;
            foreach (type_options('geo_types_vias') as $option):
              if($PACMEC['fullData']['main_road'] == $option->id) {
                $obj_main_road = $option;
                break;
              }
            endforeach;
            $obj_extra = null;
            foreach (type_options('geo_extra') as $option):
              if($PACMEC['fullData']['extra'] == $option->id) {
                $obj_extra = $option;
                break;
              }
            endforeach;
            $resutl_create = new \PACMEC\System\GeoAddresses();
            $resutl_create->country = $PACMEC['fullData']['country'];
            $resutl_create->city = $PACMEC['fullData']['city'];
            $resutl_create->main_road = $PACMEC['fullData']['main_road'];
            $resutl_create->main_road_text = $PACMEC['fullData']['main_road_text'];
            $resutl_create->minor_road = $PACMEC['fullData']['minor_road'];
            $resutl_create->number = $PACMEC['fullData']['number'];
            $resutl_create->extra = $PACMEC['fullData']['extra'];
            $resutl_create->extra_text = $PACMEC['fullData']['extra_text'];
            $resutl_create->details = isset($PACMEC['fullData']['details']) ? $PACMEC['fullData']['details'] : '';
            $resutl_create->mini = "{$obj_main_road->code} {$resutl_create->main_road_text} "
              . "# {$resutl_create->minor_road}-{$resutl_create->number} {$obj_extra->code} {$resutl_create->extra_text}"
              . ((!empty($resutl_create->details)) ? " ({$resutl_create->details})" : "")
              . ", {$obj_city->name}, {$code_country->name}";
            $resutl_create->full = "{$obj_main_road->name} {$resutl_create->main_road_text} "
              . "# {$resutl_create->minor_road}-{$resutl_create->number} {$obj_extra->name} {$resutl_create->extra_text}"
              . ((!empty($resutl_create->details)) ? " ({$resutl_create->details})" : "")
              . ", {$obj_city->name}, {$code_country->name}";
            $address_r = $resutl_create->create();
            if($resutl_create->isValid()) $address_id = $resutl_create->id;
          }

          if(
            $address_id!==false && $address_id>0
            && isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['identification_type']) && !empty($PACMEC['fullData']['identification_type'])
            && isset($PACMEC['fullData']['identification_number']) && !empty($PACMEC['fullData']['identification_number'])
            && isset($PACMEC['fullData']['names']) && !empty($PACMEC['fullData']['names'])
            && isset($PACMEC['fullData']['surname']) && !empty($PACMEC['fullData']['surname'])
            && isset($PACMEC['fullData']['email']) && !empty($PACMEC['fullData']['email'])
            && isset($PACMEC['fullData']['phone']) && !empty($PACMEC['fullData']['phone'])
            && isset($PACMEC['fullData']['mobile']) && !empty($PACMEC['fullData']['mobile'])
          ){
            $order = new PACMEC\System\Orders();
            $order->identification_type       = $PACMEC['fullData']['identification_type'];
            $order->identification_number     = $PACMEC['fullData']['identification_number'];
            $order->names                     = $PACMEC['fullData']['names'];
            $order->surname                   = $PACMEC['fullData']['surname'];
            $order->company_name              = $PACMEC['fullData']['company_name'];
            $order->email                     = $PACMEC['fullData']['email'];
            $order->phone                     = $PACMEC['fullData']['phone'];
            $order->mobile                    = $PACMEC['fullData']['mobile'];
            $order->status                    = \infosite('orders_status_default_site');
            if(isset($PACMEC['fullData']['company_name']) && !empty($PACMEC['fullData']['company_name'])) $order->company_name = $PACMEC['fullData']['company_name'];
            $order->customer_ip               = \getIpRemote();
            $order->obs                       = __a("order_create_from_site");
            $insert = $order->create();
            if($order->id>0){
              $resutl_create->add_in_order($order->id);
              // \PACMEC\System\OrdersItems::add_in_order();
              // $order
              // order_id
              // $form->setSucessMessage(__a('order_create_success'));
              $ins_carts = [];
              foreach ($PACMEC['session']->shopping_cart as $key => $item) {
                $k_vals = explode(':', $key);
                switch ($k_vals[0]) {
                  case 'product' || 'service':
                    if(isset($k_vals[1])){
                      $ab = new \PACMEC\System\OrdersItems();
                      $ab->order_id = $order->id;
                      $ab->type = $k_vals[0];
                      $ab->ref   = $k_vals[1];
                      $ab->quantity = $item->quantity;
                      $ab->unit_price = $item->data->price;
                      $result = $ab->create();
                      if($result!==false && $result>0){
                        $PACMEC['session']->remove_from_cart($item->id, $item->session_id);
                      }
                    }
                  break;
                  default:
                  break;
                }
              }
              $mail = new \PACMEC\System\EmailsTemplates((object) ['template_slug'=>infosite('mail_new_order')]);
              if($mail->isValid()){
                $mail->set_autot([
                  '%sitelogo%',
                  '%sitename%',
                  '%PreviewText%',
                  '%siteurl%',
                  '%names%',
                  '%surname%',
                  '%order_id%',
                  '%url%',
                ], [
                  infosite('siteurl').infosite('sitelogo'),
                  infosite('sitename'),
                  infosite('sitedescr'),
                  infosite('siteurl').infosite('homeurl'),
                  $order->names,
                  $order->surname,
                  $order->id,
                  $order->link_view,
                ]);
                $result_send = $mail->send(__a('order').$order->id, $order->email, "{$order->names} {$order->surname}");
              }

              $form->setSucessMessage(__a('order_create_success'));
              echo "<meta http-equiv=\"refresh\" content=\"0;URL='{$order->link_view}'\" />";
              return true;
            }
            $form->setErrorMessage(__a('error_create_order'));
            return false;
          }
          $form->setErrorMessage(__a('form_invalid'));
          return false;
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);
  $options_identifications = [''=>__a('select_an_option')];
  $options_identifications_k = [];
  foreach (type_options('identifications') as $a) $options_identifications_k["{$a->id}"] = $options_identifications["{$a->id}"] = "{$a->name} [{$a->code}]";
  $form->Code .= "<h3 class=\"title\">".__a('order_info')."</h3>";
  $form->addFieldWithLabel(
    new \PACMEC\Form\Select(
      $options_identifications
      , isset($PACMEC['fullData']['country']) ? $PACMEC['fullData']['country'] : ((\isUser()) ? "{$PACMEC['session']->user->identification_type}" : "")
      , [
        'class'  => 'myniceselect nice-select wide rounded-0'
        , 'name' => 'identification_type'
        , 'id' => 'identification_type'
        , 'required' => ''
        , 'value' => isset($PACMEC['fullData']['country']) ? $PACMEC['fullData']['country'] : ((\isUser()) ? "{$PACMEC['session']->user->identification_type}" : "")
      ]
      , [
        new \PACMEC\Form\Validation\InListValidation(__a('required_field'), array_keys($options_identifications_k))
      ]
    )
    , __a('identification_type') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 checkout-form-list']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('identification_number',
      ((\isUser()) ? "{$PACMEC['session']->user->identification_number}" : "")
    , 25, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ])
    , __a('identification_number') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 mb-6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('names',
      ((\isUser()) ? "{$PACMEC['session']->user->names}" : "")
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ])
    , __a('names') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 mb-6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('surname',
      ((\isUser()) ? "{$PACMEC['session']->user->names}" : "")
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ])
    , __a('surname') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 mb-6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('company_name',
      ""
    , 254, [
    ])
    , __a('company_name')
    , ''
    , ['pacmec-col m6 mb-6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('email',
      ((\isUser()) ? "{$PACMEC['session']->user->email}" : "")
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\EmailValidation(__a('email_invalid'))
    ])
    , __a('email') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 mb-6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('phone',
      ((\isUser()) ? "{$PACMEC['session']->user->phone}" : "")
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ], ['data-mask'=>"(00) 0000-0000"])
    , __a('phone') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 mb-6']
  );
  $form->addFieldWithLabel(
    \PACMEC\Form\Text::withNameAndValue('mobile',
      ((\isUser()) ? "{$PACMEC['session']->user->mobile}" : "")
    , 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ], ['data-mask'=>"(0#) 000-0000000"])
    , __a('mobile') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col m6 mb-6']
  );
  $form->Code .= "<h3 class=\"title\">".__a('shipping_info')."</h3>";

  if (\isUser() && count(\PACMEC\System\GeoAddresses::get_all_by_user_id())>0 && infosite('address_in_users')==true):
    $options_addressess = [''=>__a('select_an_option')];
    $options_addressess_k = [];
    foreach (\PACMEC\System\GeoAddresses::get_all_by_user_id() as $address){
      $options_addressess_k["{$address->id}"] = $options_addressess["{$address->id}"] = "{$address->mini}";
    }

    $form->addFieldWithLabel(
      new \PACMEC\Form\Select(
        $options_addressess
        , ""
        , [
          'class'  => 'pacmec-input'
          , 'name' => 'address'
          , 'id' => 'address'
          , 'required' => ''
        ]
        , [
          new \PACMEC\Form\Validation\InListValidation(__a('required_field'), array_keys($options_addressess_k))
        ]
      )
      , __a('address') . ' <span class="required">*</span>'
      , ''
      , ['m12 mb-6 checkout-form-list']
    );
  else:
    \pacmec_add_part_form_new_address($form);
  endif;

  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11)."-login", $form_slug, 'custom-pacmec'), ['single-input-item mb-3']);

  $form->addSubmitButton(__a('place_order'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'btn btn-dark btn-hover-primary rounded-0 w-100'
    //"class" => 'btn btn-sm btn-dark btn-hover-primary'
  ]);

  $form->Code .= '
  <script>
  function selectCountry(){
    Þ(document).ready(function() {
      Þ(".js-item-basic-single").select2({
        placeholder: "'.__a('select_an_option').'",
      });
    });
    Þ(\'select[name="country"]\').on(\'change\', function() {
      document.getElementById("'.$form_slug.'").submit();
    });
  }
  window.addEventListener(\'load\', selectCountry);
  </script>';

  if($form->isValid()){

  }

  return $form;
}
add_shortcode('pacmec-form-create-order-site', 'pacmec_form_create_order_site');

function pacmec_comment_form($atts=[], $content='')
{
  global $PACMEC;
  $ME = $PACMEC['session']->user;
  $args = \shortcode_atts([
    "url"=>false
  ], $atts);
  $is_error    = null;
  $msg         = null;
  $form_slug = "create-comment-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  if($args['url'] == false) $args['url'] = infosite('siteurl').$PACMEC['path'];
  $user_id = (\userID()>0) ? \userID() : null;
  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Horizontal
    , 'Error:'
    , "OK"
    , ['class'=>'pacmec-row pacmec-padding']);
  $form->setWidths(12,12);
  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $ME, $args) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':
          if(
            isset($PACMEC['fullData']["submit-{$form_slug}"])
            && isset($PACMEC['fullData']['display_name']) && !empty($PACMEC['fullData']['display_name'])
            && isset($PACMEC['fullData']['email']) && !empty($PACMEC['fullData']['email'])
            && isset($PACMEC['fullData']['comment']) && !empty($PACMEC['fullData']['comment'])
            && isset($PACMEC['fullData']['vote']) && !empty($PACMEC['fullData']['vote'])
          ){
            $comment                        = new \PACMEC\System\Comments();
            $comment->uri                   = $PACMEC['path'];
            $comment->display_name          = $PACMEC['fullData']['display_name'];
            $comment->email                 = $PACMEC['fullData']['email'];
            $comment->comment               = $PACMEC['fullData']['comment'];
            $comment->vote                  = $PACMEC['fullData']['vote'];
            if(\isUser())                   $comment->user_id = \userID();
            $comment->create();
            if($comment->isValid()){
              echo "<meta http-equiv=\"refresh\" content=\"0\" />";
              $form->setSucessMessage(__a('add_comment_r_success'));
              return true;
            } else {
              $form->setErrorMessage(__a('add_comment_r_fail'));
              return false;
            }
            $form->setErrorMessage(__a('add_comment_r_fail'));
            return false;
          }
          $form->setErrorMessage(__a('form_invalid'));
          return false;
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      $form->setErrorMessage(__a('save_fail'));
      return false;
    })
  ]);
  $form->Code .= \PACMEC\Util\Html::tag('div',
    \PACMEC\Util\Html::tag('h3', __a('add_comment_title'), ['pacmec-text-teal'])
    . \PACMEC\Util\Html::tag('p', __a('email_not_required_fields_marked_ask'), [])
    . \PACMEC\Util\Html::tag('br', '', [], [], true)
  , ['pacmec-col s12'], []);

  if(\isUser()){
    $labels = [
      "{$ME->names} {$ME->surname}" => "{$ME->names} {$ME->surname}",
      "{$ME->username}" => "{$ME->username}",
    ];

    $form->addFieldWithLabel(
      \PACMEC\Form\Select::withNameAndOptions('display_name', $labels, "{$ME->names} {$ME->surname}", array_keys($labels), ['class'=>'pacmec-input pacmec-border'])
      , __a('display_name') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12']
    );
    $form->hidden([
      [
        "name"  => "email",
        "value" => $ME->email
      ]
    ]);
  } else {
    $form->addFieldWithLabel(
      \PACMEC\Form\Text::withNameAndValue('display_name', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\MinLengthValidation(6)
      ], ["class"=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('display_name') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12']
    );
    $form->addFieldWithLabel(
      \PACMEC\Form\Text::withNameAndValue('email', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\EmailValidation(__a('email_invalid'))
        , new \PACMEC\Form\Validation\MinLengthValidation(6)
      ], ["class"=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('email') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12']
    );
  }
  $votes = [
    5 => 'Excelente',
    4 => 'Bueno',
    3 => 'Normal',
    2 => 'Bajo',
    1 => 'Pesimo',
  ];
  $form->addFieldWithLabel(
    \PACMEC\Form\Select::withNameAndOptions('vote', $votes, 5, array_keys($votes), ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
    , __a('vote') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col s12']
  );
  $form->addFieldWithLabel(
    new \PACMEC\Form\Textarea('', [
      'class'=>'pacmec-input pacmec-border pacmec-round-large',
      "name" => "comment"
    ], [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
    ], ["class"=>'pacmec-input pacmec-border pacmec-round-large'])
    , __a('comment') . ' <span class="required">*</span>'
    , ''
    , ['pacmec-col s12']
  );
  $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['s12']);
  $form->Code .= "<div class=\"clearfix\"><br></div>";
  $form->addSubmitButton(__a('add_comment_btn'), [
    'name'=>"submit-{$form_slug}",
    "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
    //"class" => 'btn btn-sm btn-dark btn-hover-primary'
  ]);
  return $form;
}
add_shortcode('pacmec-comment-form', 'pacmec_comment_form');

function pacmec_contact_form($atts, $content="")
{
  global $PACMEC;
  $form_slug = "site-contact-form-pacmec";
  $result_captcha = \pacmec_captcha_check($form_slug);
  $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Normal
    , 'Error:'
    , "OK"
    , ['class'=>'row contact-form', 'id'=>$form_slug]);
  $form->setWidths(12,12);
  $form->setGlobalValidations([
    new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form) {
      if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
      switch ($result_captcha) {
        case 'captcha_r_success':
        case 'captcha_disabled':

          $form->setErrorMessage(__a('form_invalid'));
          return false;
          break;
        default:
          $form->setErrorMessage(__a($result_captcha));
          return false;
          break;
      }
      return true;
    })
  ]);
  return $form;
  /*
  <form action="https://htmlmail.hasthemes.com/rezaul/destry.php" id="contact-form" method="post">
      <div class="row">
          <div class="col-12">
              <div class="row">
                  <div class="m6" data-aos="fade-up" data-aos-delay="300">
                      <div class="input-item mb-4">
                          <input class="input-item" type="text" placeholder="Your Name *" name="name">
                      </div>
                  </div>
                  <div class="m6" data-aos="fade-up" data-aos-delay="400">
                      <div class="input-item mb-4">
                          <input class="input-item" type="email" placeholder="Email *" name="email">
                      </div>
                  </div>
                  <div class="col-12" data-aos="fade-up" data-aos-delay="300">
                      <div class="input-item mb-4">
                          <input class="input-item" type="text" placeholder="Subject *" name="subject">
                      </div>
                  </div>
                  <div class="col-12" data-aos="fade-up" data-aos-delay="400">
                      <div class="input-item mb-8">
                          <textarea class="textarea-item" name="message" placeholder="Message"></textarea>
                      </div>
                  </div>
                  <div class="col-12" data-aos="fade-up" data-aos-delay="500">
                      <button type="submit" id="submit" name="submit" class="btn btn-dark btn-hover-primary rounded-0">Send A Message</button>
                  </div>
                  <p class="col-8 form-message mb-0"></p>
              </div>
          </div>
      </div>
  </form>
  <p class="form-messege"></p>

  <form method="POST" class="contact-form" action="<?= infosite('siteurl')."/pacmec-form-contact"; ?>" name="pacemc-contactform" id="pacemc-contactform">
    <div class="row">
      <div class="s6">
          <div class="form-group">
              <input type="text" name="name" id="name" class="form-control" placeholder="<?= _autoT('form_contact_name_placeholder'); ?>">
          </div>
          <div class="form-group">
              <input type="email" name="email" id="email" class="form-control" placeholder="<?= _autoT('form_contact_email_placeholder'); ?>">
          </div>
          <div class="form-group">
              <input type="text" name="phone" id="phone" class="form-control" placeholder="<?= _autoT('form_contact_phone_placeholder'); ?>">
          </div>
          <div class="form-group no-margin-lg">
              <input type="text" name="subject" id="subject" class="form-control" placeholder="<?= _autoT('form_contact_subject_placeholder'); ?>">
          </div>
      </div>
      <div class="s6">
          <div class="form-group">
              <textarea cols="40" rows="3" name="message" id="message" class="form-control" placeholder="<?= _autoT('form_contact_message_placeholder'); ?>"></textarea>
          </div>
          <button type="submit" id="buttonsend" class="btn btn-default btn-block"><?= _autoT('send_message'); ?></button>
      </div>
    </div>
    <span class="loading"></span>
    <div class="success-contact">
        <div class="alert alert-success" id="success-contact-alert">
            <i class="fa fa-check-circle" id="success-contact-icon"></i> <p id="success-contact-message"></p>
        </div>
    </div>
	</form>*/
}
add_shortcode('pacmec-contact-form', 'pacmec_contact_form');

function pacmec_errors($atts, $content="")
{
  global $PACMEC;
  $args = \shortcode_atts([
    "title" => 'title',
    "content" => 'content',
  ], $atts);
  return \PACMEC\Alert::leadParagraph($args['title'], $args['content'], 'danger');
}
add_shortcode('pacmec-errors', 'pacmec_errors');

function pacmec_admin_blog_table($atts, $content="")
{
  global $PACMEC;
  $article_id     = isset($PACMEC['fullData']['article_id']) ? $PACMEC['fullData']['article_id'] : false;
  $remove_article = isset($PACMEC['fullData']['remove_article']) ? $PACMEC['fullData']['remove_article'] : false;
  $create_item    = isset($PACMEC['fullData']['create_item']) ? $PACMEC['fullData']['create_item'] : false;
  $limit          = isset($PACMEC['fullData']['limit']) ? $PACMEC['fullData']['limit'] : siteinfo('pages_limit');
  $page           = isset($PACMEC['fullData']['page'])  ? $PACMEC['fullData']['page']  : 1;
  $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').__url_S("/%admin_blog_slug%");

  $html = "";
  if($article_id==false && $create_item == false && $remove_article == false){
    $info_tables    = $PACMEC['DB']->get_tables_info();
    $html .= \PACMEC\System\Posts::table_list_html_pagination(\PACMEC\System\Posts::get_all_pagination($page, $limit), $info_tables['posts']->rows, $page, $limit);
  }
  elseif ($article_id==false && $create_item == false && $remove_article == true) {
    $article = new \PACMEC\System\Posts((object) ['id' => $remove_article]);
    if($article->isValid()){
      $form_slug         = "admin-article-remove-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $form = new \PACMEC\Form\Form(
        ''
        , 'POST'
        , PACMEC\Form\FormType::Normal
        , ''
        , "OK"
        , ['class'=>'pacmec-card pacmec-light-grey pacmec-padding', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation(__a('error_form_general'), function () use ($PACMEC, $form_slug, $result_captcha, $form, $url_redirect, $article) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
            switch ($result_captcha) {
              case 'captcha_r_success':
              case 'captcha_disabled':
                if(
                  isset($PACMEC['fullData']["submit-{$form_slug}"])
                  && isset($PACMEC['fullData']["slug"]) && !empty($PACMEC['fullData']['slug'])
                  && $PACMEC['fullData']['slug'] == $article->slug
                ) {
                  $result_remove = $article->remove_this();
                  if($result_remove == true){
                    $form->setSucessMessage(
                      'OK :: '
                      . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                    );
                    return true;
                  } else {
                    $form->setErrorMessage(__a('error_remove_general'));
                    return false;
                  }
                } else {
                  $form->setErrorMessage(__a('form_invalid'));
                  return false;
                }
                break;
              default:
                $form->setErrorMessage(__a($result_captcha));
                return false;
                break;
            }
            $form->setErrorMessage(__a('form_invalid'));
            return false;
        }),
      ]);

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('slug', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\LambdaValidation(sprintf(__a('type_s_to_eq'), "\"{$article->slug}\""), function ($inputIn) use ($article) {
          return $inputIn == $article->slug;
        })
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('slug') . ' <span class="required">*</span>'
      , sprintf(__a('type_s_to_confirm'), \PACMEC\Util\Html::tag('b', $article->slug))
      , []);

      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);


      $form->addSubmitButton(__a('btn_confirm_accept'), [
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
      ]);

      /*
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_confirm_accept'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);
      $btn_submit->addHrefButton(__a('btn_confirm_decline'), $url_redirect, ['pacmec-btn pacmec-light-gray pacmec-round-large']);
      $form->Code .= $btn_submit;
      */

      return $form;
    } else {
      return "Contenido no encontrado.";
    }
  }
  elseif ($article_id==false && $create_item == true && $remove_article == false) {
    $article = new \PACMEC\System\Posts();
    $rules = $article->get_rules();

    $fields = [];
    foreach ($rules as $key => $rule) if($rule['required'] == true && $rule['auto_increment'] !== true && $rule['nullValid'] == false) $fields[] = $key;
    $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";

    $content           = isset($PACMEC['fullData']['content'])  ? $PACMEC['fullData']['content']  : $article->content;
    $style             = isset($PACMEC['fullData']['style'])  ? $PACMEC['fullData']['style']  : $article->style;
    $form_slug         = "admin-article-create-form-pacmec";
    $result_captcha    = \pacmec_captcha_check($form_slug);

    $form = new \PACMEC\Form\Form(
    ''
    , 'POST'
    , PACMEC\Form\FormType::Normal
    , 'Error:'
    , "OK"
    , ['class'=>'pacmec-container', 'id'=>$form_slug]);
    $form->setWidths(12,12);
    $form->setGlobalValidations([
      new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $article, $url_redirect) {
        if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
        switch ($result_captcha) {
          case 'captcha_r_success':
          case 'captcha_disabled':
            if(
              isset($PACMEC['fullData']['slug']) && !empty($PACMEC['fullData']['slug'])
              && isset($PACMEC['fullData']['title']) && !empty($PACMEC['fullData']['title'])
              && isset($PACMEC['fullData']['content']) && !empty($PACMEC['fullData']['content'])
              && isset($PACMEC['fullData']['tags']) && !empty($PACMEC['fullData']['tags'])
              && isset($PACMEC['fullData']['status']) && !empty($PACMEC['fullData']['status'])
            ) {
              $PACMEC['fullData']['slug'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['slug']), FILTER_SANITIZE_URL));
              $PACMEC['fullData']['tags'] = implode(',', explode(',', $PACMEC['fullData']['tags']));
              if(!isset($PACMEC['fullData']['style'])) $PACMEC['fullData']['style'] = "";
              if($article->slug !== $PACMEC['fullData']['slug']) $article->slug = $PACMEC['fullData']['slug'];
              if($article->title !== $PACMEC['fullData']['title']) $article->title = $PACMEC['fullData']['title'];
              if($article->content !== $PACMEC['fullData']['content']) $article->content = $PACMEC['fullData']['content'];
              if($article->tags !== $PACMEC['fullData']['tags']) $article->tags = $PACMEC['fullData']['tags'];
              if($article->status !== $PACMEC['fullData']['status']) $article->status = $PACMEC['fullData']['status'];
              if($article->style !== $PACMEC['fullData']['style']) $article->style = $PACMEC['fullData']['style'];
              $article->created_by = \userID();
              $result_save = $article->create([
                "slug"
                , "title"
                , "content"
                , "tags"
                , "status"
                , "style"
                , "created_by"
              ]);
              if($result_save == true){
                $form->setSucessMessage(
                  __a('save_success')
                  . "<meta http-equiv=\"refresh\" content=\"0;URL='{$article->link_edit}'\" />"
                );
                return true;
              } else {
                $form->setErrorMessage(__a('save_fail'));
                return false;
              }
            }
            $form->setErrorMessage(__a('form_invalid'));
            return false;
            break;
          default:
            $form->setErrorMessage(__a($result_captcha));
            return false;
            break;
        }
        $form->setErrorMessage(__a('form_invalid'));
        return false;
      }),
    ]);
    /*
    foreach ($rules as $key => $rule) {
      if($rule['required'] == true && $rule['auto_increment'] !== true && $rule['nullValid'] == false){
        $form->addFieldWithLabel(
          \PACMEC\Form\Text::withNameAndValue($rule['name'],
          $rule['name']
          , 254, [
            new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          ], [
            "class" => 'pacmec-input pacmec-border pacmec-round-large'
          ])
          , __a($rule['name']) . ' <span class="required">*</span>'
          , ''
        , []);
      }
    }
    */
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('title', '', 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ], [
      "class" => 'pacmec-input pacmec-border pacmec-round-large'
    ])
    , __a('title') . ' <span class="required">*</span>'
    , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('slug', '', 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ], [
      "class" => 'pacmec-input pacmec-border pacmec-round-large'
    ])
    , __a('slug') . ' <span class="required">*</span>'
    , ''
    , []);

    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('tags', '', 254, [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
    ], [
      "class"             => "pacmec-input pacmec-border pacmec-round-large",
      "data-role"         => "tagsinput",
    ])
    , __a('tags') . ' <span class="required">*</span>'
    , ''
    , []);

    $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('status', [
      ""          => "Seleccione una opcion",
      "publish"   => "publicado",
      "draft"     => "Borrador",
    ], $article->status, ['publish',"draft"], ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
    , __a('status')
    , ''
    , []);

    if(infosite('grapesjs_enable')==true) $form->Code .= "<div id=\"gjs\">{$content}<style>{$style}</style></div>";

    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($content, [
      'class'  => 'pacmec-input pacmec-border pacmec-round-large',
      "name"   => "content",
      "id"     => "html",
    ], [
      new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      , new \PACMEC\Form\Validation\MinLengthValidation(6)
    ])
    , __a('content') . ' <span class="required">*</span>'
    , ''
    , [
      ((infosite('grapesjs_enable')==true) ? "hidden" : "")
    ]);
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($style, [
      'class'=>'pacmec-input pacmec-border pacmec-round-large',
      "name" => "style",
      "id" => "css"
    ], [
    ])
    , __a('style')
    , ''
    , [((infosite('grapesjs_enable')==true) ? "hidden" : "")]);
    $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
    $form->Code .= "<div class=\"clearfix\"><br></div>";
    $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
      'id'=>"submit-{$form_slug}",
      'name'=>"submit-{$form_slug}",
      "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
    ]);
    if(isset($PACMEC['fullData']['redirect'])) $btn_submit->addHrefButton(__a('btn_cancel'), $PACMEC['fullData']['redirect'], ['pacmec-btn pacmec-gray pacmec-round-large']);
    $form->Code .= $btn_submit;
    if (infosite('grapesjs_enable')==true) {
      $form->Code .= '
      <style>
        .gjs-block {
          width: auto;
          height: auto;
          min-height: auto;
        }
      </style>
      <script>
        var editor = grapesjs.init({
          height: \'calc(60vh)\',
          container: \'#gjs\',
          showOffsets: true,
          fromElement: true,
          noticeOnUnload: false,
          storageManager: false,
          // plugins: ["gjs-preset-webpage", "gjs-preset-newsletter"],
          plugins: [
            "gjs-preset-webpage"
            ,"gjs-preset-newsletter"
          ],
        });
        const htmlTextarea = document.getElementById(\'html\')
        const cssTextarea = document.getElementById(\'css\')
        const updateTextarea = (component, editor)=>{
          const e = component.em.get("Editor");
          htmlTextarea.value= e.getHtml();
          cssTextarea.value= e.getCss();
        }
        editor.on(\'component:add\', updateTextarea);
        editor.on(\'component:update\', updateTextarea);
        editor.on(\'component:remove\', updateTextarea);
        const updateInstance = () => {
          editor.setComponents(htmlTextarea.value)
          editor.setStyle(cssTextarea.value)
        }
        document.getElementById(\'submit-'.$form_slug.'\').onclick=updateInstance;
      </script>';
    }
    $html .= $form;
    return $html;
  }
  else {
    $article = new \PACMEC\System\Posts((object) ['id'=>$article_id]);
    if($article->isValid()){
      $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";
      $content           = isset($PACMEC['fullData']['content'])  ? $PACMEC['fullData']['content']  : $article->content;
      $style             = isset($PACMEC['fullData']['style'])  ? $PACMEC['fullData']['style']  : $article->style;
      $form_slug         = "admin-article-edit-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').$PACMEC['path'];
      $form = new \PACMEC\Form\Form(
      ''
      , 'POST'
      , PACMEC\Form\FormType::Normal
      , 'Error:'
      , "OK"
      , ['class'=>'pacmec-container', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $article, $url_redirect) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
          switch ($result_captcha) {
            case 'captcha_r_success':
            case 'captcha_disabled':
              if(
                isset($PACMEC['fullData']['slug']) && !empty($PACMEC['fullData']['slug'])
                && isset($PACMEC['fullData']['title']) && !empty($PACMEC['fullData']['title'])
                && isset($PACMEC['fullData']['content']) && !empty($PACMEC['fullData']['content'])
                && isset($PACMEC['fullData']['tags']) && !empty($PACMEC['fullData']['tags'])
                && isset($PACMEC['fullData']['status']) && !empty($PACMEC['fullData']['status'])
              ) {
                $PACMEC['fullData']['slug'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['slug']), FILTER_SANITIZE_URL));
                $PACMEC['fullData']['tags'] = implode(',', explode(',', $PACMEC['fullData']['tags']));
                if(!isset($PACMEC['fullData']['style'])) $PACMEC['fullData']['style'] = "";
                if($article->slug !== $PACMEC['fullData']['slug']) $article->slug = $PACMEC['fullData']['slug'];
                if($article->title !== $PACMEC['fullData']['title']) $article->title = $PACMEC['fullData']['title'];
                if($article->content !== $PACMEC['fullData']['content']) $article->content = $PACMEC['fullData']['content'];
                if($article->tags !== $PACMEC['fullData']['tags']) $article->tags = $PACMEC['fullData']['tags'];
                if($article->status !== $PACMEC['fullData']['status']) $article->status = $PACMEC['fullData']['status'];
                if($article->style !== $PACMEC['fullData']['style']) $article->style = $PACMEC['fullData']['style'];
                $result_save = $article->save([
                  "slug"
                  , "title"
                  , "content"
                  , "tags"
                  , "status"
                  , "style"
                ]);
                if($result_save == true){
                  $form->setSucessMessage(
                    __a('save_success')
                    . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                  );
                  return true;
                } else {
                  $form->setErrorMessage(__a('save_fail'));
                  return false;
                }
              }
              $form->setErrorMessage(__a('form_invalid'));
              return false;
              break;
            default:
              $form->setErrorMessage(__a($result_captcha));
              return false;
              break;
          }
          $form->setErrorMessage(__a('form_invalid'));
          return false;
        }),
      ]);
      $form->addFieldWithLabel(
        \PACMEC\Form\Text::withNameAndValue('title',
        $article->title
        , 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('title') . ' <span class="required">*</span>'
        , ''
          /*\PACMEC\Form\Text::withNameAndValue('slug',
          $article->slug
          , 254, [
            new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          ], [
            "class" => 'pacmec-input pacmec-border pacmec-round-large'
          ])*/
        , []
      );
      $form->addFieldWithLabel(
        \PACMEC\Form\Text::withNameAndValue('slug',
        $article->slug
        , 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          //"data-mask"=>"A#-#",
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('slug') . ' <span class="required">*</span>'
        , ''
        // , 'URL -> ' . __url_S("/%blog_read_slug%/{$article->id}/slug")
        , []
      );
      $form->addFieldWithLabel(
        \PACMEC\Form\Text::withNameAndValue('tags',
        implode(',', $article->tags)
        , 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          //"data-mask"=>"A#-#",
          "class"             => "pacmec-input pacmec-border pacmec-round-large",
          "data-role"         => "tagsinput",
        ])
        , __a('tags') . ' <span class="required">*</span>'
        , ''
        , []
      );
      $form->addFieldWithLabel(
        \PACMEC\Form\Select::withNameAndOptions('status', [
          ""          => "Seleccione una opcion",
          "publish"   => "publicado",
          "draft"     => "Borrador",
        ], $article->status, ['publish',"draft"], ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
        , __a('status')
        , ''
        , []
      );
      if(infosite('grapesjs_enable')==true) $form->Code .= "<div id=\"gjs\">{$content}<style>{$style}</style></div>";
      $form->addFieldWithLabel(
        new \PACMEC\Form\Textarea($content, [
          'class'  => 'pacmec-input pacmec-border pacmec-round-large',
          "name"   => "content",
          "id"     => "html",
        ], [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          , new \PACMEC\Form\Validation\MinLengthValidation(6)
        ])
        , __a('content') . ' <span class="required">*</span>'
        , ''
        , [((infosite('grapesjs_enable')==true) ? "hidden" : "")]
      );
      $form->addFieldWithLabel(
        new \PACMEC\Form\Textarea($style, [
          'class'=>'pacmec-input pacmec-border pacmec-round-large',
          "name" => "style",
          "id" => "css"
        ], [
        ])
        , __a('style')
        , ''
        , [((infosite('grapesjs_enable')==true) ? "hidden" : "")]
      );
      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
      $form->Code .= "<div class=\"clearfix\"><br></div>";
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);

      $btn_submit->addHrefButton(__a('btn_cancel'), ((isset($PACMEC['fullData']['redirect'])) ? $PACMEC['fullData']['redirect'] : __url_s("/%admin_blog_slug%")), ['pacmec-btn pacmec-gray pacmec-round-large']);

      $form->Code .= $btn_submit;

      if (infosite('grapesjs_enable')==true) {
        $form->Code .= '
        <style>
          .gjs-block {
            width: auto;
            height: auto;
            min-height: auto;
          }
        </style>
        <script>
          var editor = grapesjs.init({
            height: \'calc(60vh)\',
            container: \'#gjs\',
            showOffsets: true,
            fromElement: true,
            noticeOnUnload: false,
            storageManager: false,
            // plugins: ["gjs-preset-webpage", "gjs-preset-newsletter"],
            plugins: [
              "gjs-preset-webpage"
              ,"gjs-preset-newsletter"
            ],
          });
          const htmlTextarea = document.getElementById(\'html\')
          const cssTextarea = document.getElementById(\'css\')
          const updateTextarea = (component, editor)=>{
            const e = component.em.get("Editor");
            htmlTextarea.value= e.getHtml();
            cssTextarea.value= e.getCss();
          }
          editor.on(\'component:add\', updateTextarea);
          editor.on(\'component:update\', updateTextarea);
          editor.on(\'component:remove\', updateTextarea);
          const updateInstance = () => {
            editor.setComponents(htmlTextarea.value)
            editor.setStyle(cssTextarea.value)
          }
          document.getElementById(\'submit-'.$form_slug.'\').onclick=updateInstance;
        </script>';
      }
      $html .= $form;
    } else {
      $html .= "Articulo no encontrado";
    }
  }
  return $html;
}
add_shortcode('pacmec-admin-blog-table', 'pacmec_admin_blog_table');

function pacmec_console_orders($atts, $content="")
{
  $html = "";
  $html .= \PACMEC\Html\Util::tag('div', 'PENDIENTE', ['pacmec'] , []);
  return $html;
}
add_shortcode('pacmec-console-orders', 'pacmec_console_orders');

function pacmec_theme_console($atts, $content="")
{
 $args = \shortcode_atts([
   "console" => 'user',
 ], $atts);
 return get_template_part("template-parts/consoles/{$args['console']}");
}
add_shortcode('pacmec-theme-console', 'pacmec_theme_console');

function pacmec_admin_services_table($atts, $content="")
{
  global $PACMEC;
  $import         = isset($PACMEC['fullData']['import']) ? true : false;
  $service_id     = isset($PACMEC['fullData']['service_id']) ? $PACMEC['fullData']['service_id'] : false;
  $remove_service = isset($PACMEC['fullData']['remove_service']) ? $PACMEC['fullData']['remove_service'] : false;
  $create_item    = isset($PACMEC['fullData']['create_item']) ? $PACMEC['fullData']['create_item'] : false;
  $limit          = isset($PACMEC['fullData']['limit']) ? $PACMEC['fullData']['limit'] : siteinfo('pages_limit');
  $page           = isset($PACMEC['fullData']['page'])  ? $PACMEC['fullData']['page']  : 1;
  $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').__url_S("/%admin_services_slug%");

  $html = "";
  if($import == true){
    $info_tables   = $PACMEC['DB']->get_tables_info();

    if(isset($_FILES['excel']) && $_FILES['excel']['error']==0) {
  		$tmpfname    = $_FILES['excel']['tmp_name'];
  		$excelReader = \PHPExcel_IOFactory::createReaderForFile($tmpfname);
  		$excelObj    = $excelReader->load($tmpfname);
  		$worksheet   = $excelObj->getSheet(0);
  		$lastRow     = $worksheet->getHighestRow();
      $__columns   = [];
      $items       = [];
  		echo "<div class=\"pacmec-responsive\">";
    		echo "<table class=\"pacmec-table-all table-s\">";
          $t_columns = count($info_tables['services']->columns);
          for ($i=0; $i < count($info_tables['services']->columns); $i++) {
            if(!empty($worksheet->getCellByColumnAndRow($i, 1)->getValue())){
              $__columns[\getNameFromNumberZero($i)] = $worksheet->getCellByColumnAndRow($i, 1)->getValue();
            }
          }
          for ($row = 1; $row <= $lastRow; $row++) {
            $item[] = [];
            foreach ($__columns as $l => $k) {
                $item[$k] = $worksheet->getCell("{$l}{$row}")->getValue();
            }
            $items[] = $item;
          }
          echo "<thead>";
            echo "<tr>";
              echo "<th scope=\"row\"></th>";
              echo "<th scope=\"row\">A</th>";
              echo "<th scope=\"row\">B</th>";
              echo "<th scope=\"row\">C</th>";
              echo "<th scope=\"row\">Resultado</th>";
            echo "</tr>";
            foreach ($items as $i => $item) {
              echo "<tr>";
                echo "<th scope=\"row\">";
                  echo ($i+1);
                echo "</th>";

                echo "<th>{$item['id']}</th>";
                echo "<th>{$item['slug']}</th>";
                echo "<th>{$item['name']}</th>";
                  # if($item['id'])
                  if($i == 0){
                    echo "<th scope=\"row\"></th>";
                  } else {
                    if(empty($item['id'])) {
                      echo "<th>";
                        $new_item = new \PACMEC\System\Service();
                        foreach (array_keys($item) as $k) {
                          if($k !== 'id') $new_item->{$k} = $item[$k];
                        }
                        $new_item->created_by = \userID();
                        $result_save = $new_item->create(array_keys($item));
                        if($result_save == true && $new_item->isValid()){
                          echo " <i class=\"fa fa-check\"></i> Creado {$new_item->id}";
                        } else {
                          echo " <i class=\"fa fa-times\"></i> Error creando";
                        }
                      echo "</th>";
                    } else {
                      echo "<th>";
                      $new_item = new \PACMEC\System\Service((object) ['id'=>$item['id']]);
                      if($new_item->isValid())
                      {
                        echo "servicio encontrado";
                        foreach (array_keys($item) as $k) {
                          $new_item->{$k} = $item[$k];
                        }
                        $result_save = $new_item->save(array_keys($item));
                        if($result_save == true){
                          echo " <i class=\"fa fa-check\"></i> Actualizado";
                        } else {
                          echo " <i class=\"fa fa-times\"></i> Error actualizando";
                        }
                      } else {
                          echo "servicio NO encontrado";
                      }
                      echo "</th>";
                    };
                  }
                //echo "<th scope=\"row\">{$item['slug']}</th>";
              echo "</tr>";
            }
          echo "</thead>";
        echo "</table>";
  		echo "</div>";
    } else {
      #echo "no existe archivo para actualizar";
      $form_slug         = "admin-service-import-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $form = new \PACMEC\Form\Form(
        ''
        , 'POST'
        , PACMEC\Form\FormType::Normal
        , ''
        , "OK"
        , ['class' => 'pacmec-card pacmec-light-grey pacmec-padding', 'id' => $form_slug, 'enctype' => "multipart/form-data" ]);
      $form->setWidths(12,12);
      $form->addFieldWithLabel(new \PACMEC\Form\File(
        [
          "class" => 'pacmec-input pacmec-border pacmec-round-large',
          "type" => "file",
          "name" => "excel"
        ], [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ])
      , __a('file') . ' <span class="required">*</span>'
      , '', []);
      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
      $form->addSubmitButton(__a('btn_confirm_accept'), [
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
      ]);
      echo $form;
    }

  }
  else if($service_id==false && $create_item == false && $remove_service == false){
    $info_tables    = $PACMEC['DB']->get_tables_info();
    $html .= \PACMEC\System\Service::table_list_html_pagination(\PACMEC\System\Service::get_all_pagination($page, $limit), $info_tables['services']->rows, $page, $limit);
  }
  elseif ($service_id==false && $create_item == false && $remove_service == true) {
    $service = new \PACMEC\System\Service((object) ['id' => $remove_service]);
    if($service->isValid()){
      $form_slug         = "admin-service-remove-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $form = new \PACMEC\Form\Form(
        ''
        , 'POST'
        , PACMEC\Form\FormType::Normal
        , ''
        , "OK"
        , ['class'=>'pacmec-card pacmec-light-grey pacmec-padding', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation(__a('error_form_general'), function () use ($PACMEC, $form_slug, $result_captcha, $form, $url_redirect, $service) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
            switch ($result_captcha) {
              case 'captcha_r_success':
              case 'captcha_disabled':
                if(isset($PACMEC['fullData']["submit-{$form_slug}"]) && isset($PACMEC['fullData']["slug"]) && !empty($PACMEC['fullData']['slug']) && $PACMEC['fullData']['slug'] == $service->slug) {
                  $result_remove = $service->remove_this();
                  if($result_remove == true){
                    $form->setSucessMessage(
                      'OK :: ' . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                    );
                    return true;
                  } else {
                    $form->setErrorMessage(__a('error_remove_general'));
                    return false;
                  }
                } else {
                  $form->setErrorMessage(__a('form_invalid'));
                  return false;
                }
                break;
              default:
                $form->setErrorMessage(__a($result_captcha));
                return false;
                break;
            }
            $form->setErrorMessage(__a('form_invalid'));
            return false;
        }),
      ]);

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('slug', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\LambdaValidation(sprintf(__a('type_s_to_eq'), "\"{$service->slug}\""), function ($inputIn) use ($service) {
          return $inputIn == $service->slug;
        })
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large',
        "autocomplete" => "off"
      ])
      , __a('slug_ref') . ' <span class="required">*</span>'
      , sprintf(__a('type_s_to_confirm'), \PACMEC\Util\Html::tag('b', $service->slug))
      , []);
      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
      $form->addSubmitButton(__a('btn_confirm_accept'), [
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
      ]);
      return $form;
    } else {
      return "Contenido no encontrado.";
    }
  }
  elseif ($service_id==false && $create_item == true && $remove_service == false) {
    $service = new \PACMEC\System\Service();
    $rules = $service->get_rules();
    $fields = [];
    foreach ($rules as $key => $rule) if($rule['required'] == true && $rule['auto_increment'] !== true && $rule['nullValid'] == false) $fields[] = $key;
    $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";
    $description_full           = isset($PACMEC['fullData']['description_full'])  ? $PACMEC['fullData']['description_full']  : $service->description_full;
    $description_full_style     = isset($PACMEC['fullData']['description_full_style'])  ? $PACMEC['fullData']['description_full_style']  : $service->description_full_style;
    $form_slug         = "admin-service-create-form-pacmec";
    $result_captcha    = \pacmec_captcha_check($form_slug);
    $form = new \PACMEC\Form\Form(
      ''
      , 'POST'
      , PACMEC\Form\FormType::Normal
      , 'Error:'
      , "OK"
      , ['class'=>'pacmec-container', 'id'=>$form_slug]
    );
    $form->setWidths(12,12);
    $form->setGlobalValidations([
      new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $service, $url_redirect) {
        if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
        switch ($result_captcha) {
          case 'captcha_r_success':
          case 'captcha_disabled':
            if(
              isset($PACMEC['fullData']['slug']) && !empty($PACMEC['fullData']['slug'])
              && isset($PACMEC['fullData']['name']) && !empty($PACMEC['fullData']['name'])
              && isset($PACMEC['fullData']['description']) && !empty($PACMEC['fullData']['description'])
              && isset($PACMEC['fullData']['description_full'])
              && isset($PACMEC['fullData']['unid']) && !empty($PACMEC['fullData']['unid'])
              && isset($PACMEC['fullData']['is_active']) && !empty($PACMEC['fullData']['is_active'])
              && isset($PACMEC['fullData']['icon'])
              && isset($PACMEC['fullData']['price_normal'])
              && isset($PACMEC['fullData']['price_promo'])
              && isset($PACMEC['fullData']['observations'])
            ) {
              $PACMEC['fullData']['slug'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['slug']), FILTER_SANITIZE_URL));
              $PACMEC['fullData']['common_names'] = implode(',', explode(',', $PACMEC['fullData']['common_names']));
              if(!isset($PACMEC['fullData']['description_full_style'])) $PACMEC['fullData']['description_full_style'] = "";
              if($service->slug !== $PACMEC['fullData']['slug']) $service->slug = $PACMEC['fullData']['slug'];
              if($service->name !== $PACMEC['fullData']['name']) $service->name = $PACMEC['fullData']['name'];
              if($service->description !== $PACMEC['fullData']['description']) $service->description = $PACMEC['fullData']['description'];
              if($service->common_names !== $PACMEC['fullData']['common_names']) $service->common_names = $PACMEC['fullData']['common_names'];
              if($service->unid !== $PACMEC['fullData']['unid']) $service->unid = $PACMEC['fullData']['unid'];
              if($service->is_active !== $PACMEC['fullData']['is_active']) $service->is_active = $PACMEC['fullData']['is_active'];
              if($service->icon !== $PACMEC['fullData']['icon']) $service->icon = $PACMEC['fullData']['icon'];
              if($service->price_normal !== $PACMEC['fullData']['price_normal']) $service->price_normal = $PACMEC['fullData']['price_normal'];
              if($service->price_promo !== $PACMEC['fullData']['price_promo']) $service->price_promo = $PACMEC['fullData']['price_promo'];
              if($service->observations !== $PACMEC['fullData']['observations']) $service->observations = $PACMEC['fullData']['observations'];
              if($service->description_full !== $PACMEC['fullData']['description_full']) $service->description_full = $PACMEC['fullData']['description_full'];
              if($service->description_full_style !== $PACMEC['fullData']['description_full_style']) $service->description_full_style = $PACMEC['fullData']['description_full_style'];
              $service->created_by = \userID();
              $result_save = $service->create([
                "slug"
                , "name"
                , "description"
                , "description_full"
                , "description_full_style"
                , "common_names"
                , "unid"
                , "is_active"
                , "created_by"
                , "icon"
                , "price_normal"
                , "price_promo"
                , "observations"
              ]);
              if($result_save == true){
                $form->setSucessMessage(
                  __a('save_success')
                  . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                );
                return true;
              } else {
                $form->setErrorMessage(__a('save_fail'));
                return false;
              }
            } else {
              $form->setErrorMessage(__a('form_invalid'));
              return false;
            }
            break;
          default:
            $form->setErrorMessage(__a($result_captcha));
            return false;
            break;
        }
        $form->setErrorMessage(__a('form_invalid'));
        return false;
      }),
    ]);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('name', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('name') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('slug', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('slug_ref') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea('', [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "description",
      ], [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\MinLengthValidation(6)
      ])
      , __a('description') . ' <span class="required">*</span>'
      , ''
      , [
    ]);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('common_names', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"             => "pacmec-input pacmec-border pacmec-round-large",
        "data-role"         => "tagsinput",
      ])
      , __a('common_names') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('is_active', [
        ""          => "Seleccione una opcion",
        "1"   => "servicio disponible en la tienda",
        "0"     => "servicio oculto",
      ], $service->is_active, ['1',"0"], ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('status')
      , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('icon', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('icon') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $obj_unids = [""=>"Seleccione una opcion"];
    $obj_unids_k = [];
    foreach (type_options('measurement_units') as $option){
      $obj_unids[$option->code]   = $option->name;
      $obj_unids_k[] = $option->code;
    }
    $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('unid', $obj_unids, '', $obj_unids_k, ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
    , __a('unid')
    , ''
    , ['pacmec-col s12 m3']);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_normal', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('price_normal') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_promo', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('price_promo') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea('', [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "observations",
      ], [
      ])
      , __a('observations')
      , 'Estas observaciones solo serán visibles por el personal que gestiona las ordenes/servicios/servicios.'
      , [
    ]);
    $form->Code .= \PACMEC\Util\Html::clearfix();
    if(infosite('grapesjs_enable')==true) $form->Code .= "<br><div class=\"pacmec-col s12\" id=\"gjs\">{$description_full}<style>{$description_full_style}</style></div>";
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full, [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "description_full",
        "id"     => "html",
      ], [
        #new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        #, new \PACMEC\Form\Validation\MinLengthValidation(6)
      ])
      , __a('description_full') . ' <span class="required">*</span>'
      , ''
      , [
        ((infosite('grapesjs_enable')==true) ? "hidden" : "")
    ]);
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full_style, [
        'class'=>'pacmec-input pacmec-border pacmec-round-large',
        "name" => "description_full_style",
        "id" => "css",
      ], [
      ])
      , __a('description_full_style')
      , ''
      , [
        ((infosite('grapesjs_enable')==true) ? "hidden" : "")
    ]);
    $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
    $form->Code .= "<div class=\"clearfix\"><br></div>";
    $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
      'id'=>"submit-{$form_slug}",
      'name'=>"submit-{$form_slug}",
      "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
    ]);
    if(isset($PACMEC['fullData']['redirect'])) $btn_submit->addHrefButton(__a('btn_cancel'), $PACMEC['fullData']['redirect'], ['pacmec-btn pacmec-gray pacmec-round-large']);
    $form->Code .= $btn_submit;
    if (infosite('grapesjs_enable')==true) {
      $form->Code .= '
      <style>
        .gjs-block {
          width: auto;
          height: auto;
          min-height: auto;
        }
      </style>
      <script>
        var editor = grapesjs.init({
          height: \'calc(60vh)\',
          container: \'#gjs\',
          showOffsets: true,
          fromElement: true,
          noticeOnUnload: false,
          storageManager: false,
          // plugins: ["gjs-preset-webpage", "gjs-preset-newsletter"],
          plugins: [
            "gjs-preset-webpage"
            ,"gjs-preset-newsletter"
          ],
        });
        const htmlTextarea = document.getElementById(\'html\')
        const cssTextarea = document.getElementById(\'css\')
        const updateTextarea = (component, editor)=>{
          const e = component.em.get("Editor");
          htmlTextarea.value= e.getHtml();
          cssTextarea.value= e.getCss();
        }
        editor.on(\'component:add\', updateTextarea);
        editor.on(\'component:update\', updateTextarea);
        editor.on(\'component:remove\', updateTextarea);
        const updateInstance = () => {
          editor.setComponents(htmlTextarea.value)
          editor.setStyle(cssTextarea.value)
        }
        document.getElementById(\'submit-'.$form_slug.'\').onclick=updateInstance;';
      $form->Code .= "Þ(document).ready(function(){
          // Þ('.number_format').mask(\"#.##0\", {reverse: true});
        });";
      $form->Code .= '</script>';
    }
    $html .= $form;
    return $html;
  }
  else {
    $service = new \PACMEC\System\Service((object) ['id'=>$service_id]);
    if($service->isValid()){
      $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";
      $description_full           = isset($PACMEC['fullData']['description_full'])  ? $PACMEC['fullData']['description_full']  : $service->description_full;
      $description_full_style     = isset($PACMEC['fullData']['description_full_style'])  ? $PACMEC['fullData']['description_full_style']  : $service->description_full_style;
      $form_slug         = "admin-service-edit-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').$PACMEC['path'];
      $form = new \PACMEC\Form\Form(
      ''
      , 'POST'
      , PACMEC\Form\FormType::Normal
      , 'Error:'
      , "OK"
      , ['class'=>'pacmec-container', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $service, $url_redirect) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
          switch ($result_captcha) {
            case 'captcha_r_success':
            case 'captcha_disabled':
              if(
                isset($PACMEC['fullData']['slug']) && !empty($PACMEC['fullData']['slug'])
                && isset($PACMEC['fullData']['name']) && !empty($PACMEC['fullData']['name'])
                && isset($PACMEC['fullData']['description']) && !empty($PACMEC['fullData']['description'])
                && isset($PACMEC['fullData']['description_full'])
                && isset($PACMEC['fullData']['unid']) && !empty($PACMEC['fullData']['unid'])
                && isset($PACMEC['fullData']['is_active']) && !empty($PACMEC['fullData']['is_active'])
                && isset($PACMEC['fullData']['icon'])
                && isset($PACMEC['fullData']['price_normal'])
                && isset($PACMEC['fullData']['price_promo'])
                && isset($PACMEC['fullData']['observations'])
              ) {
                $PACMEC['fullData']['slug'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['slug']), FILTER_SANITIZE_URL));
                $PACMEC['fullData']['common_names'] = implode(',', explode(',', $PACMEC['fullData']['common_names']));
                if(!isset($PACMEC['fullData']['description_full_style'])) $PACMEC['fullData']['description_full_style'] = "";
                if($service->slug !== $PACMEC['fullData']['slug']) $service->slug = $PACMEC['fullData']['slug'];
                if($service->name !== $PACMEC['fullData']['name']) $service->name = $PACMEC['fullData']['name'];
                if($service->description !== $PACMEC['fullData']['description']) $service->description = $PACMEC['fullData']['description'];
                if($service->common_names !== $PACMEC['fullData']['common_names']) $service->common_names = $PACMEC['fullData']['common_names'];
                if($service->unid !== $PACMEC['fullData']['unid']) $service->unid = $PACMEC['fullData']['unid'];
                if($service->is_active !== $PACMEC['fullData']['is_active']) $service->is_active = $PACMEC['fullData']['is_active'];
                if($service->icon !== $PACMEC['fullData']['icon']) $service->icon = $PACMEC['fullData']['icon'];
                if($service->price_normal !== $PACMEC['fullData']['price_normal']) $service->price_normal = $PACMEC['fullData']['price_normal'];
                if($service->price_promo !== $PACMEC['fullData']['price_promo']) $service->price_promo = (float) $PACMEC['fullData']['price_promo'];
                if($service->observations !== $PACMEC['fullData']['observations']) $service->observations = $PACMEC['fullData']['observations'];
                if($service->description_full !== $PACMEC['fullData']['description_full']) $service->description_full = $PACMEC['fullData']['description_full'];
                if($service->description_full_style !== $PACMEC['fullData']['description_full_style']) $service->description_full_style = $PACMEC['fullData']['description_full_style'];
                $service->created_by = \userID();

                $result_save = $service->save([
                  "slug"
                  , "name"
                  , "description"
                  , "description_full"
                  , "description_full_style"
                  , "common_names"
                  , "unid"
                  , "is_active"
                  , "created_by"
                  , "icon"
                  , "price_normal"
                  , "price_promo"
                  , "observations"
                ]);
                if($result_save == true){
                  $form->setSucessMessage(
                    __a('save_success')
                    . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                  );
                  return true;
                } else {
                  $form->setErrorMessage(__a('save_fail'));
                  return false;
                }
              }
              $form->setErrorMessage(__a('form_invalid'));
              return false;
              break;
            default:
              $form->setErrorMessage(__a($result_captcha));
              return false;
              break;
          }
          $form->setErrorMessage(__a('form_invalid'));
          return false;
        }),
      ]);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('name', $service->name, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('name') . ' <span class="required">*</span>'
        , ''
      , []);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('slug', $service->slug, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('slug_ref') . ' <span class="required">*</span>'
        , ''
      , []);
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($service->description, [
          'class'  => 'pacmec-input pacmec-border pacmec-round-large',
          "name"   => "description",
        ], [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          , new \PACMEC\Form\Validation\MinLengthValidation(6)
        ])
        , __a('description') . ' <span class="required">*</span>'
        , ''
        , [
      ]);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('common_names', implode(',', $service->common_names), 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"             => "pacmec-input pacmec-border pacmec-round-large",
          "data-role"         => "tagsinput",
        ])
        , __a('common_names') . ' <span class="required">*</span>'
        , ''
      , []);
      $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('is_active', [
          ""          => "Seleccione una opcion",
          "1"   => "servicio disponible en la tienda",
          "0"     => "servicio oculto",
        ], $service->is_active, ['1',"0"], ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
        , __a('status')
        , ''
      , []);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('icon', $service->icon, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
        ])
        , __a('icon') . ' <span class="required">*</span>'
        , ''
        , ['pacmec-col s12 m3']
      );
      $obj_unids = [""=>"Seleccione una opcion"];
      $obj_unids_k = [];
      foreach (type_options('measurement_units') as $option){
        $obj_unids[$option->code]   = $option->name;
        $obj_unids_k[] = $option->code;
      }
      $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('unid', $obj_unids, $service->unid, $obj_unids_k, ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('unid')
      , ''
      , ['pacmec-col s12 m3']);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_normal', $service->price_normal, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"     => "pacmec-input pacmec-border pacmec-round-large number_format_",
        ])
        , __a('price_normal') . ' <span class="required">*</span>'
        , ''
        , ['pacmec-col s12 m3']
      );
      $form->addFieldWithLabel(\PACMEC\Form\Number::withNameAndValue('price_promo', $service->price_promo, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"     => "pacmec-input pacmec-border pacmec-round-large number_format_",
        ])
        , __a('price_promo') . ' <span class="required">*</span>'
        , ''
        , ['pacmec-col s12 m3']
      );
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($service->observations, [
          'class'  => 'pacmec-input pacmec-border pacmec-round-large',
          "name"   => "observations",
        ], [
        ])
        , __a('observations')
        , 'Estas observaciones solo serán visibles por el personal que gestiona las ordenes/servicios/servicios.'
        , [
      ]);
      $form->Code .= \PACMEC\Util\Html::clearfix();
      if(infosite('grapesjs_enable')==true) $form->Code .= "<br><div class=\"pacmec-col s12\" id=\"gjs\">{$description_full}<style>{$description_full_style}</style></div>";
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full, [
          'class'  => 'pacmec-input pacmec-border pacmec-round-large',
          "name"   => "description_full",
          "id"     => "html",
        ], [
          #new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          #, new \PACMEC\Form\Validation\MinLengthValidation(6)
        ])
        , __a('description_full') . ' <span class="required">*</span>'
        , ''
        , [
          ((infosite('grapesjs_enable')==true) ? "hidden" : "")
      ]);
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full_style, [
          'class'=>'pacmec-input pacmec-border pacmec-round-large',
          "name" => "description_full_style",
          "id" => "css",
        ], [
        ])
        , __a('description_full_style')
        , ''
        , [
          ((infosite('grapesjs_enable')==true) ? "hidden" : "")
      ]);
      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
      $form->Code .= "<div class=\"clearfix\"><br></div>";
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);
      $form->Code .= "<div class=\"clearfix\"><br></div>";
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);

      $btn_submit->addHrefButton(__a('btn_cancel'), ((isset($PACMEC['fullData']['redirect'])) ? $PACMEC['fullData']['redirect'] : __url_s("/%admin_blog_slug%")), ['pacmec-btn pacmec-gray pacmec-round-large']);

      $form->Code .= $btn_submit;

      if (infosite('grapesjs_enable')==true) {
        $form->Code .= '
        <style>
          .gjs-block {
            width: auto;
            height: auto;
            min-height: auto;
          }
        </style>
        <script>
          var editor = grapesjs.init({
            height: \'calc(60vh)\',
            container: \'#gjs\',
            showOffsets: true,
            fromElement: true,
            noticeOnUnload: false,
            storageManager: false,
            // plugins: ["gjs-preset-webpage", "gjs-preset-newsletter"],
            plugins: [
              "gjs-preset-webpage"
              ,"gjs-preset-newsletter"
            ],
          });
          const htmlTextarea = document.getElementById(\'html\')
          const cssTextarea = document.getElementById(\'css\')
          const updateTextarea = (component, editor)=>{
            const e = component.em.get("Editor");
            htmlTextarea.value= e.getHtml();
            cssTextarea.value= e.getCss();
          }
          editor.on(\'component:add\', updateTextarea);
          editor.on(\'component:update\', updateTextarea);
          editor.on(\'component:remove\', updateTextarea);
          const updateInstance = () => {
            editor.setComponents(htmlTextarea.value)
            editor.setStyle(cssTextarea.value)
          }
          document.getElementById(\'submit-'.$form_slug.'\').onclick=updateInstance;
        </script>';
      }
      $html .= $form;
    } else {
      $html .= "servicio no encontrado";
    }
  }
  return $html;
}
add_shortcode('pacmec-admin-services-table', 'pacmec_admin_services_table');

function pacmec_admin_products_table($atts, $content="")
{
  global $PACMEC;
  $import         = isset($PACMEC['fullData']['import']) ? true : false;
  $product_id     = isset($PACMEC['fullData']['product_id']) ? $PACMEC['fullData']['product_id'] : false;
  $remove_product = isset($PACMEC['fullData']['remove_product']) ? $PACMEC['fullData']['remove_product'] : false;
  $create_item    = isset($PACMEC['fullData']['create_item']) ? $PACMEC['fullData']['create_item'] : false;
  $limit          = isset($PACMEC['fullData']['limit']) ? $PACMEC['fullData']['limit'] : siteinfo('pages_limit');
  $page           = isset($PACMEC['fullData']['page'])  ? $PACMEC['fullData']['page']  : 1;
  $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').__url_S("/%admin_products_slug%");

  $html = "";
  if($import == true){
    $info_tables   = $PACMEC['DB']->get_tables_info();

    if(isset($_FILES['excel']) && $_FILES['excel']['error']==0) {
  		$tmpfname    = $_FILES['excel']['tmp_name'];
  		$excelReader = \PHPExcel_IOFactory::createReaderForFile($tmpfname);
  		$excelObj    = $excelReader->load($tmpfname);
  		$worksheet   = $excelObj->getSheet(0);
  		$lastRow     = $worksheet->getHighestRow();
      $__columns   = [];
      $items       = [];
  		echo "<div class=\"pacmec-responsive\">";
    		echo "<table class=\"pacmec-table-all table-s\">";
          $t_columns = count($info_tables['products']->columns);
          for ($i=0; $i < count($info_tables['products']->columns); $i++) {
            if(!empty($worksheet->getCellByColumnAndRow($i, 1)->getValue())){
              $__columns[\getNameFromNumberZero($i)] = $worksheet->getCellByColumnAndRow($i, 1)->getValue();
            }
          }
          for ($row = 1; $row <= $lastRow; $row++) {
            $item[] = [];
            foreach ($__columns as $l => $k) {
                $item[$k] = $worksheet->getCell("{$l}{$row}")->getValue();
            }
            $items[] = $item;
          }
          echo "<thead>";
            echo "<tr>";
              echo "<th scope=\"row\"></th>";
              echo "<th scope=\"row\">A</th>";
              echo "<th scope=\"row\">B</th>";
              echo "<th scope=\"row\">C</th>";
              echo "<th scope=\"row\">Resultado</th>";
            echo "</tr>";
            foreach ($items as $i => $item) {
              echo "<tr>";
                echo "<th scope=\"row\">";
                  echo ($i+1);
                echo "</th>";

                echo "<th>{$item['id']}</th>";
                echo "<th>{$item['sku']}</th>";
                echo "<th>{$item['name']}</th>";
                  # if($item['id'])
                  if($i == 0){
                    echo "<th scope=\"row\"></th>";
                  } else {
                    if(empty($item['id'])) {
                      echo "<th>";
                        $new_item = new \PACMEC\System\Product();
                        foreach (array_keys($item) as $k) {
                          if($k !== 'id') $new_item->{$k} = $item[$k];
                        }
                        $new_item->created_by = \userID();
                        $result_save = $new_item->create(array_keys($item));
                        if($result_save == true && $new_item->isValid()){
                          echo " <i class=\"fa fa-check\"></i> Creado {$new_item->id}";
                        } else {
                          echo " <i class=\"fa fa-times\"></i> Error creando";
                        }
                      echo "</th>";
                    } else {
                      echo "<th>";
                      $new_item = new \PACMEC\System\Product((object) ['id'=>$item['id']]);
                      if($new_item->isValid())
                      {
                        echo "Producto encontrado";
                        foreach (array_keys($item) as $k) {
                          $new_item->{$k} = $item[$k];
                        }
                        $result_save = $new_item->save(array_keys($item));
                        if($result_save == true){
                          echo " <i class=\"fa fa-check\"></i> Actualizado";
                        } else {
                          echo " <i class=\"fa fa-times\"></i> Error actualizando";
                        }
                      } else {
                          echo "Producto NO encontrado";
                      }
                      echo "</th>";
                    };
                  }
                //echo "<th scope=\"row\">{$item['sku']}</th>";
              echo "</tr>";
            }
          echo "</thead>";
        echo "</table>";
  		echo "</div>";
    } else {
      #echo "no existe archivo para actualizar";
      $form_slug         = "admin-product-import-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $form = new \PACMEC\Form\Form(
        ''
        , 'POST'
        , PACMEC\Form\FormType::Normal
        , ''
        , "OK"
        , ['class' => 'pacmec-card pacmec-light-grey pacmec-padding', 'id' => $form_slug, 'enctype' => "multipart/form-data" ]);
      $form->setWidths(12,12);


      $form->addFieldWithLabel(new \PACMEC\Form\File(
        [
          "class" => 'pacmec-input pacmec-border pacmec-round-large',
          "type" => "file",
          "name" => "excel"
        ], [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ])
      , __a('file') . ' <span class="required">*</span>'
      , '', []);


      /*<form action = "" method = "POST" enctype = "multipart/form-data">
      	<h2 for="myfile1">Select files : </h2>
               <input type = "file" name = "excel" onchange="ValidateSingleInput(this)" />
               <input type = "submit"/><br><br>
      </form>*/
      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);

      $form->addSubmitButton(__a('btn_confirm_accept'), [
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
      ]);
      echo $form;
    }

  }
  else if($product_id==false && $create_item == false && $remove_product == false){
    $info_tables    = $PACMEC['DB']->get_tables_info();
    $html .= \PACMEC\System\Product::table_list_html_pagination(\PACMEC\System\Product::get_all_pagination($page, $limit), $info_tables['products']->rows, $page, $limit);
  }
  elseif ($product_id==false && $create_item == false && $remove_product == true) {
    $product = new \PACMEC\System\Product((object) ['id' => $remove_product]);
    if($product->isValid()){
      $form_slug         = "admin-product-remove-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $form = new \PACMEC\Form\Form(
        ''
        , 'POST'
        , PACMEC\Form\FormType::Normal
        , ''
        , "OK"
        , ['class'=>'pacmec-card pacmec-light-grey pacmec-padding', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation(__a('error_form_general'), function () use ($PACMEC, $form_slug, $result_captcha, $form, $url_redirect, $product) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
            switch ($result_captcha) {
              case 'captcha_r_success':
              case 'captcha_disabled':
                if(isset($PACMEC['fullData']["submit-{$form_slug}"]) && isset($PACMEC['fullData']["sku"]) && !empty($PACMEC['fullData']['sku']) && $PACMEC['fullData']['sku'] == $product->sku) {
                  $result_remove = $product->remove_this();
                  if($result_remove == true){
                    $form->setSucessMessage(
                      'OK :: ' . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                    );
                    return true;
                  } else {
                    $form->setErrorMessage(__a('error_remove_general'));
                    return false;
                  }
                } else {
                  $form->setErrorMessage(__a('form_invalid'));
                  return false;
                }
                break;
              default:
                $form->setErrorMessage(__a($result_captcha));
                return false;
                break;
            }
            $form->setErrorMessage(__a('form_invalid'));
            return false;
        }),
      ]);

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('sku', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\LambdaValidation(sprintf(__a('type_s_to_eq'), "\"{$product->sku}\""), function ($inputIn) use ($product) {
          return $inputIn == $product->sku;
        })
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large',
        "autocomplete" => "off"
      ])
      , __a('sku_ref') . ' <span class="required">*</span>'
      , sprintf(__a('type_s_to_confirm'), \PACMEC\Util\Html::tag('b', $product->sku))
      , []);

      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);


      $form->addSubmitButton(__a('btn_confirm_accept'), [
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
      ]);

      /*
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_confirm_accept'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);
      $btn_submit->addHrefButton(__a('btn_confirm_decline'), $url_redirect, ['pacmec-btn pacmec-light-gray pacmec-round-large']);
      $form->Code .= $btn_submit;
      */

      return $form;
    } else {
      return "Contenido no encontrado.";
    }
  }
  elseif ($product_id==false && $create_item == true && $remove_product == false) {
    $product = new \PACMEC\System\Product();
    $rules = $product->get_rules();
    $fields = [];
    foreach ($rules as $key => $rule) if($rule['required'] == true && $rule['auto_increment'] !== true && $rule['nullValid'] == false) $fields[] = $key;
    $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";
    $description_full           = isset($PACMEC['fullData']['description_full'])  ? $PACMEC['fullData']['description_full']  : $product->description_full;
    $description_full_style     = isset($PACMEC['fullData']['description_full_style'])  ? $PACMEC['fullData']['description_full_style']  : $product->description_full_style;
    $form_slug         = "admin-product-create-form-pacmec";
    $result_captcha    = \pacmec_captcha_check($form_slug);
    $form = new \PACMEC\Form\Form(
      ''
      , 'POST'
      , PACMEC\Form\FormType::Normal
      , 'Error:'
      , "OK"
      , ['class'=>'pacmec-container', 'id'=>$form_slug]
    );
    $form->setWidths(12,12);
    $form->setGlobalValidations([
      new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $product, $url_redirect) {
        if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
        switch ($result_captcha) {
          case 'captcha_r_success':
          case 'captcha_disabled':
            if(
              isset($PACMEC['fullData']['sku']) && !empty($PACMEC['fullData']['sku'])
              && isset($PACMEC['fullData']['name']) && !empty($PACMEC['fullData']['name'])
              && isset($PACMEC['fullData']['description']) && !empty($PACMEC['fullData']['description'])
              && isset($PACMEC['fullData']['description_full'])
              && isset($PACMEC['fullData']['unid']) && !empty($PACMEC['fullData']['unid'])
              && isset($PACMEC['fullData']['is_active']) && !empty($PACMEC['fullData']['is_active'])
              && isset($PACMEC['fullData']['available'])
              && isset($PACMEC['fullData']['price_normal'])
              && isset($PACMEC['fullData']['price_promo'])
              && isset($PACMEC['fullData']['observations'])
            ) {
              $PACMEC['fullData']['sku'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['sku']), FILTER_SANITIZE_URL));
              $PACMEC['fullData']['common_names'] = implode(',', explode(',', $PACMEC['fullData']['common_names']));
              if(!isset($PACMEC['fullData']['description_full_style'])) $PACMEC['fullData']['description_full_style'] = "";
              if($product->sku !== $PACMEC['fullData']['sku']) $product->sku = $PACMEC['fullData']['sku'];
              if($product->name !== $PACMEC['fullData']['name']) $product->name = $PACMEC['fullData']['name'];
              if($product->description !== $PACMEC['fullData']['description']) $product->description = $PACMEC['fullData']['description'];
              if($product->common_names !== $PACMEC['fullData']['common_names']) $product->common_names = $PACMEC['fullData']['common_names'];
              if($product->unid !== $PACMEC['fullData']['unid']) $product->unid = $PACMEC['fullData']['unid'];
              if($product->is_active !== $PACMEC['fullData']['is_active']) $product->is_active = $PACMEC['fullData']['is_active'];
              if($product->available !== $PACMEC['fullData']['available']) $product->available = $PACMEC['fullData']['available'];
              if($product->price_normal !== $PACMEC['fullData']['price_normal']) $product->price_normal = $PACMEC['fullData']['price_normal'];
              if($product->price_promo !== $PACMEC['fullData']['price_promo']) $product->price_promo = $PACMEC['fullData']['price_promo'];
              if($product->observations !== $PACMEC['fullData']['observations']) $product->observations = $PACMEC['fullData']['observations'];
              if($product->description_full !== $PACMEC['fullData']['description_full']) $product->description_full = $PACMEC['fullData']['description_full'];
              if($product->description_full_style !== $PACMEC['fullData']['description_full_style']) $product->description_full_style = $PACMEC['fullData']['description_full_style'];
              $product->created_by = \userID();
              $result_save = $product->create([
                "sku"
                , "name"
                , "description"
                , "description_full"
                , "description_full_style"
                , "common_names"
                , "unid"
                , "is_active"
                , "created_by"
                , "available"
                , "price_normal"
                , "price_promo"
                , "observations"
              ]);
              if($result_save == true){
                $form->setSucessMessage(
                  __a('save_success')
                  . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                );
                return true;
              } else {
                $form->setErrorMessage(__a('save_fail'));
                return false;
              }
            } else {
              $form->setErrorMessage(__a('form_invalid'));
              return false;
            }
            break;
          default:
            $form->setErrorMessage(__a($result_captcha));
            return false;
            break;
        }
        $form->setErrorMessage(__a('form_invalid'));
        return false;
      }),
    ]);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('name', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('name') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('sku', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('sku_ref') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea('', [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "description",
      ], [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\MinLengthValidation(6)
      ])
      , __a('description') . ' <span class="required">*</span>'
      , ''
      , [
    ]);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('common_names', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"             => "pacmec-input pacmec-border pacmec-round-large",
        "data-role"         => "tagsinput",
      ])
      , __a('common_names') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('is_active', [
        ""          => "Seleccione una opcion",
        "1"   => "Producto disponible en la tienda",
        "0"     => "Producto oculto",
      ], $product->is_active, ['1',"0"], ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('status')
      , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('available', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('available') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $obj_unids = [""=>"Seleccione una opcion"];
    $obj_unids_k = [];
    foreach (type_options('measurement_units') as $option){
      $obj_unids[$option->code]   = $option->name;
      $obj_unids_k[] = $option->code;
    }
    $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('unid', $obj_unids, '', $obj_unids_k, ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
    , __a('unid')
    , ''
    , ['pacmec-col s12 m3']);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_normal', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('price_normal') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_promo', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('price_promo') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea('', [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "observations",
      ], [
      ])
      , __a('observations')
      , 'Estas observaciones solo serán visibles por el personal que gestiona las ordenes/productos/servicios.'
      , [
    ]);
    $form->Code .= \PACMEC\Util\Html::clearfix();
    if(infosite('grapesjs_enable')==true) $form->Code .= "<br><div class=\"pacmec-col s12\" id=\"gjs\">{$description_full}<style>{$description_full_style}</style></div>";
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full, [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "description_full",
        "id"     => "html",
      ], [
        #new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        #, new \PACMEC\Form\Validation\MinLengthValidation(6)
      ])
      , __a('description_full') . ' <span class="required">*</span>'
      , ''
      , [
        ((infosite('grapesjs_enable')==true) ? "hidden" : "")
    ]);
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full_style, [
        'class'=>'pacmec-input pacmec-border pacmec-round-large',
        "name" => "description_full_style",
        "id" => "css",
      ], [
      ])
      , __a('description_full_style')
      , ''
      , [
        ((infosite('grapesjs_enable')==true) ? "hidden" : "")
    ]);
    $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
    $form->Code .= "<div class=\"clearfix\"><br></div>";
    $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
      'id'=>"submit-{$form_slug}",
      'name'=>"submit-{$form_slug}",
      "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
    ]);
    if(isset($PACMEC['fullData']['redirect'])) $btn_submit->addHrefButton(__a('btn_cancel'), $PACMEC['fullData']['redirect'], ['pacmec-btn pacmec-gray pacmec-round-large']);
    $form->Code .= $btn_submit;
    if (infosite('grapesjs_enable')==true) {
      $form->Code .= '
      <style>
        .gjs-block {
          width: auto;
          height: auto;
          min-height: auto;
        }
      </style>
      <script>
        var editor = grapesjs.init({
          height: \'calc(60vh)\',
          container: \'#gjs\',
          showOffsets: true,
          fromElement: true,
          noticeOnUnload: false,
          storageManager: false,
          // plugins: ["gjs-preset-webpage", "gjs-preset-newsletter"],
          plugins: [
            "gjs-preset-webpage"
            ,"gjs-preset-newsletter"
          ],
        });
        const htmlTextarea = document.getElementById(\'html\')
        const cssTextarea = document.getElementById(\'css\')
        const updateTextarea = (component, editor)=>{
          const e = component.em.get("Editor");
          htmlTextarea.value= e.getHtml();
          cssTextarea.value= e.getCss();
        }
        editor.on(\'component:add\', updateTextarea);
        editor.on(\'component:update\', updateTextarea);
        editor.on(\'component:remove\', updateTextarea);
        const updateInstance = () => {
          editor.setComponents(htmlTextarea.value)
          editor.setStyle(cssTextarea.value)
        }
        document.getElementById(\'submit-'.$form_slug.'\').onclick=updateInstance;';

      $form->Code .= "Þ(document).ready(function(){
          // Þ('.number_format').mask(\"#.##0\", {reverse: true});
        });";
      $form->Code .= '</script>';
    }
    $html .= $form;
    return $html;
  }
  else {
    $product = new \PACMEC\System\Product((object) ['id'=>$product_id]);
    if($product->isValid()){
      $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";
      $description_full           = isset($PACMEC['fullData']['description_full'])  ? $PACMEC['fullData']['description_full']  : $product->description_full;
      $description_full_style     = isset($PACMEC['fullData']['description_full_style'])  ? $PACMEC['fullData']['description_full_style']  : $product->description_full_style;
      $form_slug         = "admin-product-edit-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').$PACMEC['path'];
      $form = new \PACMEC\Form\Form(
      ''
      , 'POST'
      , PACMEC\Form\FormType::Normal
      , 'Error:'
      , "OK"
      , ['class'=>'pacmec-container', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $product, $url_redirect) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
          switch ($result_captcha) {
            case 'captcha_r_success':
            case 'captcha_disabled':
              if(
                isset($PACMEC['fullData']['sku']) && !empty($PACMEC['fullData']['sku'])
                && isset($PACMEC['fullData']['name']) && !empty($PACMEC['fullData']['name'])
                && isset($PACMEC['fullData']['description']) && !empty($PACMEC['fullData']['description'])
                && isset($PACMEC['fullData']['description_full'])
                && isset($PACMEC['fullData']['unid']) && !empty($PACMEC['fullData']['unid'])
                && isset($PACMEC['fullData']['is_active']) && !empty($PACMEC['fullData']['is_active'])
                && isset($PACMEC['fullData']['available'])
                && isset($PACMEC['fullData']['price_normal'])
                && isset($PACMEC['fullData']['price_promo'])
                && isset($PACMEC['fullData']['observations'])
              ) {
                $PACMEC['fullData']['sku'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['sku']), FILTER_SANITIZE_URL));
                $PACMEC['fullData']['common_names'] = implode(',', explode(',', $PACMEC['fullData']['common_names']));
                if(!isset($PACMEC['fullData']['description_full_style'])) $PACMEC['fullData']['description_full_style'] = "";
                if($product->sku !== $PACMEC['fullData']['sku']) $product->sku = $PACMEC['fullData']['sku'];
                if($product->name !== $PACMEC['fullData']['name']) $product->name = $PACMEC['fullData']['name'];
                if($product->description !== $PACMEC['fullData']['description']) $product->description = $PACMEC['fullData']['description'];
                if($product->common_names !== $PACMEC['fullData']['common_names']) $product->common_names = $PACMEC['fullData']['common_names'];
                if($product->unid !== $PACMEC['fullData']['unid']) $product->unid = $PACMEC['fullData']['unid'];
                if($product->is_active !== $PACMEC['fullData']['is_active']) $product->is_active = $PACMEC['fullData']['is_active'];
                if($product->available !== $PACMEC['fullData']['available']) $product->available = $PACMEC['fullData']['available'];
                if($product->price_normal !== $PACMEC['fullData']['price_normal']) $product->price_normal = $PACMEC['fullData']['price_normal'];
                if($product->price_promo !== $PACMEC['fullData']['price_promo']) $product->price_promo = (float) $PACMEC['fullData']['price_promo'];
                if($product->observations !== $PACMEC['fullData']['observations']) $product->observations = $PACMEC['fullData']['observations'];
                if($product->description_full !== $PACMEC['fullData']['description_full']) $product->description_full = $PACMEC['fullData']['description_full'];
                if($product->description_full_style !== $PACMEC['fullData']['description_full_style']) $product->description_full_style = $PACMEC['fullData']['description_full_style'];
                $product->created_by = \userID();

                $result_save = $product->save([
                  "sku"
                  , "name"
                  , "description"
                  , "description_full"
                  , "description_full_style"
                  , "common_names"
                  , "unid"
                  , "is_active"
                  , "created_by"
                  , "available"
                  , "price_normal"
                  , "price_promo"
                  , "observations"
                ]);
                if($result_save == true){
                  $form->setSucessMessage(
                    __a('save_success')
                    . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                  );
                  return true;
                } else {
                  $form->setErrorMessage(__a('save_fail'));
                  return false;
                }
              }
              $form->setErrorMessage(__a('form_invalid'));
              return false;
              break;
            default:
              $form->setErrorMessage(__a($result_captcha));
              return false;
              break;
          }
          $form->setErrorMessage(__a('form_invalid'));
          return false;
        }),
      ]);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('name', $product->name, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('name') . ' <span class="required">*</span>'
        , ''
      , []);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('sku', $product->sku, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('sku_ref') . ' <span class="required">*</span>'
        , ''
      , []);
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($product->description, [
          'class'  => 'pacmec-input pacmec-border pacmec-round-large',
          "name"   => "description",
        ], [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          , new \PACMEC\Form\Validation\MinLengthValidation(6)
        ])
        , __a('description') . ' <span class="required">*</span>'
        , ''
        , [
      ]);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('common_names', implode(',', $product->common_names), 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"             => "pacmec-input pacmec-border pacmec-round-large",
          "data-role"         => "tagsinput",
        ])
        , __a('common_names') . ' <span class="required">*</span>'
        , ''
      , []);
      $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('is_active', [
          ""          => "Seleccione una opcion",
          "1"   => "Producto disponible en la tienda",
          "0"     => "Producto oculto",
        ], $product->is_active, ['1',"0"], ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
        , __a('status')
        , ''
      , []);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('available', $product->available, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
        ])
        , __a('available') . ' <span class="required">*</span>'
        , ''
        , ['pacmec-col s12 m3']
      );
      $obj_unids = [""=>"Seleccione una opcion"];
      $obj_unids_k = [];
      foreach (type_options('measurement_units') as $option){
        $obj_unids[$option->code]   = $option->name;
        $obj_unids_k[] = $option->code;
      }
      $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('unid', $obj_unids, $product->unid, $obj_unids_k, ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('unid')
      , ''
      , ['pacmec-col s12 m3']);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_normal', $product->price_normal, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"     => "pacmec-input pacmec-border pacmec-round-large number_format_",
        ])
        , __a('price_normal') . ' <span class="required">*</span>'
        , ''
        , ['pacmec-col s12 m3']
      );
      $form->addFieldWithLabel(\PACMEC\Form\Number::withNameAndValue('price_promo', $product->price_promo, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class"     => "pacmec-input pacmec-border pacmec-round-large number_format_",
        ])
        , __a('price_promo') . ' <span class="required">*</span>'
        , ''
        , ['pacmec-col s12 m3']
      );
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($product->observations, [
          'class'  => 'pacmec-input pacmec-border pacmec-round-large',
          "name"   => "observations",
        ], [
        ])
        , __a('observations')
        , 'Estas observaciones solo serán visibles por el personal que gestiona las ordenes/productos/servicios.'
        , [
      ]);
      $form->Code .= \PACMEC\Util\Html::clearfix();
      if(infosite('grapesjs_enable')==true) $form->Code .= "<br><div class=\"pacmec-col s12\" id=\"gjs\">{$description_full}<style>{$description_full_style}</style></div>";
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full, [
          'class'  => 'pacmec-input pacmec-border pacmec-round-large',
          "name"   => "description_full",
          "id"     => "html",
        ], [
          #new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
          #, new \PACMEC\Form\Validation\MinLengthValidation(6)
        ])
        , __a('description_full') . ' <span class="required">*</span>'
        , ''
        , [
          ((infosite('grapesjs_enable')==true) ? "hidden" : "")
      ]);
      $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full_style, [
          'class'=>'pacmec-input pacmec-border pacmec-round-large',
          "name" => "description_full_style",
          "id" => "css",
        ], [
        ])
        , __a('description_full_style')
        , ''
        , [
          ((infosite('grapesjs_enable')==true) ? "hidden" : "")
      ]);
      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
      $form->Code .= "<div class=\"clearfix\"><br></div>";
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);
      $form->Code .= "<div class=\"clearfix\"><br></div>";
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);

      $btn_submit->addHrefButton(__a('btn_cancel'), ((isset($PACMEC['fullData']['redirect'])) ? $PACMEC['fullData']['redirect'] : __url_s("/%admin_blog_slug%")), ['pacmec-btn pacmec-gray pacmec-round-large']);

      $form->Code .= $btn_submit;

      if (infosite('grapesjs_enable')==true) {
        $form->Code .= '
        <style>
          .gjs-block {
            width: auto;
            height: auto;
            min-height: auto;
          }
        </style>
        <script>
          var editor = grapesjs.init({
            height: \'calc(60vh)\',
            container: \'#gjs\',
            showOffsets: true,
            fromElement: true,
            noticeOnUnload: false,
            storageManager: false,
            // plugins: ["gjs-preset-webpage", "gjs-preset-newsletter"],
            plugins: [
              "gjs-preset-webpage"
              ,"gjs-preset-newsletter"
            ],
          });
          const htmlTextarea = document.getElementById(\'html\')
          const cssTextarea = document.getElementById(\'css\')
          const updateTextarea = (component, editor)=>{
            const e = component.em.get("Editor");
            htmlTextarea.value= e.getHtml();
            cssTextarea.value= e.getCss();
          }
          editor.on(\'component:add\', updateTextarea);
          editor.on(\'component:update\', updateTextarea);
          editor.on(\'component:remove\', updateTextarea);
          const updateInstance = () => {
            editor.setComponents(htmlTextarea.value)
            editor.setStyle(cssTextarea.value)
          }
          document.getElementById(\'submit-'.$form_slug.'\').onclick=updateInstance;
        </script>';
      }
      $html .= $form;
    } else {
      $html .= "Producto no encontrado";
    }
  }
  return $html;
}
add_shortcode('pacmec-admin-products-table', 'pacmec_admin_products_table');

function pacmec_admin_galleries_shop_table($atts, $content="")
{
  global $PACMEC;
  $reload         = isset($PACMEC['fullData']['reload']) ? true : false;
  $limit          = isset($PACMEC['fullData']['limit']) ? $PACMEC['fullData']['limit'] : siteinfo('pages_limit');
  $page           = isset($PACMEC['fullData']['page'])  ? $PACMEC['fullData']['page']  : 1;
  $html = "";
  $info_tables    = $PACMEC['DB']->get_tables_info();
  if($reload == true){
    #$html .= json_encode(\PACMEC\System\GalleriesShop::get_all_in_gallery(), JSON_PRETTY_PRINT);
    echo \PACMEC\System\GalleriesShop::reload_all_galleries_shop();
  } else {
    $html .= \PACMEC\System\GalleriesShop::table_list_html_galleries_reload(\PACMEC\System\GalleriesShop::get_all_in_gallery());
  }
  return $html;
}
add_shortcode('pacmec-admin-galleries-shop-table', 'pacmec_admin_galleries_shop_table');

function pacmec_admin_clients_table($atts, $content="")
{
  global $PACMEC;
  $import         = isset($PACMEC['fullData']['import']) ? true : false;
  $client_id     = isset($PACMEC['fullData']['client_id']) ? $PACMEC['fullData']['client_id'] : false;
  $remove_client = isset($PACMEC['fullData']['remove_client']) ? $PACMEC['fullData']['remove_client'] : false;
  $create_item    = isset($PACMEC['fullData']['create_item']) ? $PACMEC['fullData']['create_item'] : false;
  $limit          = isset($PACMEC['fullData']['limit']) ? $PACMEC['fullData']['limit'] : siteinfo('pages_limit');
  $page           = isset($PACMEC['fullData']['page'])  ? $PACMEC['fullData']['page']  : 1;
  $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').__url_S("/%admin_clients_slug%");

  $html = "";
  if($import == true){
    $info_tables   = $PACMEC['DB']->get_tables_info();

    if(isset($_FILES['excel']) && $_FILES['excel']['error']==0) {
  		$tmpfname    = $_FILES['excel']['tmp_name'];
  		$excelReader = \PHPExcel_IOFactory::createReaderForFile($tmpfname);
  		$excelObj    = $excelReader->load($tmpfname);
  		$worksheet   = $excelObj->getSheet(0);
  		$lastRow     = $worksheet->getHighestRow();
      $__columns   = [];
      $items       = [];
  		echo "<div class=\"pacmec-responsive\">";
    		echo "<table class=\"pacmec-table-all table-s\">";
          $t_columns = count($info_tables['clients']->columns);
          for ($i=0; $i < count($info_tables['clients']->columns); $i++) {
            if(!empty($worksheet->getCellByColumnAndRow($i, 1)->getValue())){
              $__columns[\getNameFromNumberZero($i)] = $worksheet->getCellByColumnAndRow($i, 1)->getValue();
            }
          }
          for ($row = 1; $row <= $lastRow; $row++) {
            $item[] = [];
            foreach ($__columns as $l => $k) {
                $item[$k] = $worksheet->getCell("{$l}{$row}")->getValue();
            }
            $items[] = $item;
          }
          echo "<thead>";
            echo "<tr>";
              echo "<th scope=\"row\"></th>";
              echo "<th scope=\"row\">A</th>";
              echo "<th scope=\"row\">B</th>";
              echo "<th scope=\"row\">C</th>";
              echo "<th scope=\"row\">Resultado</th>";
            echo "</tr>";
            foreach ($items as $i => $item) {
              echo "<tr>";
                echo "<th scope=\"row\">";
                  echo ($i+1);
                echo "</th>";

                echo "<th>{$item['id']}</th>";
                echo "<th>{$item['sku']}</th>";
                echo "<th>{$item['name']}</th>";
                  # if($item['id'])
                  if($i == 0){
                    echo "<th scope=\"row\"></th>";
                  } else {
                    if(empty($item['id'])) {
                      echo "<th>";
                        $new_item = new \PACMEC\System\Client();
                        foreach (array_keys($item) as $k) {
                          if($k !== 'id') $new_item->{$k} = $item[$k];
                        }
                        $new_item->created_by = \userID();
                        $result_save = $new_item->create(array_keys($item));
                        if($result_save == true && $new_item->isValid()){
                          echo " <i class=\"fa fa-check\"></i> Creado {$new_item->id}";
                        } else {
                          echo " <i class=\"fa fa-times\"></i> Error creando";
                        }
                      echo "</th>";
                    } else {
                      echo "<th>";
                      $new_item = new \PACMEC\System\Client((object) ['id'=>$item['id']]);
                      if($new_item->isValid())
                      {
                        echo "Cliento encontrado";
                        foreach (array_keys($item) as $k) {
                          $new_item->{$k} = $item[$k];
                        }
                        $result_save = $new_item->save(array_keys($item));
                        if($result_save == true){
                          echo " <i class=\"fa fa-check\"></i> Actualizado";
                        } else {
                          echo " <i class=\"fa fa-times\"></i> Error actualizando";
                        }
                      } else {
                          echo "Cliento NO encontrado";
                      }
                      echo "</th>";
                    };
                  }
                //echo "<th scope=\"row\">{$item['sku']}</th>";
              echo "</tr>";
            }
          echo "</thead>";
        echo "</table>";
  		echo "</div>";
    } else {
      #echo "no existe archivo para actualizar";
      $form_slug         = "admin-client-import-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $form = new \PACMEC\Form\Form(
        ''
        , 'POST'
        , PACMEC\Form\FormType::Normal
        , ''
        , "OK"
        , ['class' => 'pacmec-card pacmec-light-grey pacmec-padding', 'id' => $form_slug, 'enctype' => "multipart/form-data" ]);
      $form->setWidths(12,12);


      $form->addFieldWithLabel(new \PACMEC\Form\File(
        [
          "class" => 'pacmec-input pacmec-border pacmec-round-large',
          "type" => "file",
          "name" => "excel"
        ], [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ])
      , __a('file') . ' <span class="required">*</span>'
      , '', []);


      /*<form action = "" method = "POST" enctype = "multipart/form-data">
      	<h2 for="myfile1">Select files : </h2>
               <input type = "file" name = "excel" onchange="ValidateSingleInput(this)" />
               <input type = "submit"/><br><br>
      </form>*/
      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);

      $form->addSubmitButton(__a('btn_confirm_accept'), [
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
      ]);
      echo $form;
    }

  }
  else if($client_id==false && $create_item == false && $remove_client == false){
    $info_tables    = $PACMEC['DB']->get_tables_info();
    $html .= \PACMEC\System\Client::table_list_html_pagination(\PACMEC\System\Client::get_all_pagination($page, $limit), $info_tables['clients']->rows, $page, $limit);
  }
  elseif ($client_id==false && $create_item == false && $remove_client == true) {
    $client = new \PACMEC\System\Client((object) ['id' => $remove_client]);
    if($client->isValid()){
      $form_slug         = "admin-client-remove-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $form = new \PACMEC\Form\Form(
        ''
        , 'POST'
        , PACMEC\Form\FormType::Normal
        , ''
        , "OK"
        , ['class'=>'pacmec-card pacmec-light-grey pacmec-padding', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation(__a('error_form_general'), function () use ($PACMEC, $form_slug, $result_captcha, $form, $url_redirect, $client) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
            switch ($result_captcha) {
              case 'captcha_r_success':
              case 'captcha_disabled':
                if(isset($PACMEC['fullData']["submit-{$form_slug}"]) && isset($PACMEC['fullData']["sku"]) && !empty($PACMEC['fullData']['sku']) && $PACMEC['fullData']['sku'] == $client->sku) {
                  $result_remove = $client->remove_this();
                  if($result_remove == true){
                    $form->setSucessMessage(
                      'OK :: ' . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                    );
                    return true;
                  } else {
                    $form->setErrorMessage(__a('error_remove_general'));
                    return false;
                  }
                } else {
                  $form->setErrorMessage(__a('form_invalid'));
                  return false;
                }
                break;
              default:
                $form->setErrorMessage(__a($result_captcha));
                return false;
                break;
            }
            $form->setErrorMessage(__a('form_invalid'));
            return false;
        }),
      ]);

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('sku', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\LambdaValidation(sprintf(__a('type_s_to_eq'), "\"{$client->sku}\""), function ($inputIn) use ($client) {
          return $inputIn == $client->sku;
        })
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large',
        "autocomplete" => "off"
      ])
      , __a('sku_ref') . ' <span class="required">*</span>'
      , sprintf(__a('type_s_to_confirm'), \PACMEC\Util\Html::tag('b', $client->sku))
      , []);

      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);


      $form->addSubmitButton(__a('btn_confirm_accept'), [
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-button pacmec-green pacmec-round-large w-100'
      ]);

      /*
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_confirm_accept'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);
      $btn_submit->addHrefButton(__a('btn_confirm_decline'), $url_redirect, ['pacmec-btn pacmec-light-gray pacmec-round-large']);
      $form->Code .= $btn_submit;
      */

      return $form;
    } else {
      return "Contenido no encontrado.";
    }
  }
  elseif ($client_id==false && $create_item == true && $remove_client == false) {
    $client = new \PACMEC\System\Client();
    $rules = $client->get_rules();
    $fields = [];
    foreach ($rules as $key => $rule) if($rule['required'] == true && $rule['auto_increment'] !== true && $rule['nullValid'] == false) $fields[] = $key;
    $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";
    $description_full           = "";
    $description_full_style     = "";
    $form_slug         = "admin-client-create-form-pacmec";
    $result_captcha    = \pacmec_captcha_check($form_slug);
    $form = new \PACMEC\Form\Form(
      ''
      , 'POST'
      , PACMEC\Form\FormType::Normal
      , 'Error:'
      , "OK"
      , ['class'=>'pacmec-container', 'id'=>$form_slug]
    );
    $form->setWidths(12,12);
    $form->setGlobalValidations([
      new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $client, $url_redirect) {
        if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
        switch ($result_captcha) {
          case 'captcha_r_success':
          case 'captcha_disabled':
            if(
              isset($PACMEC['fullData']['sku']) && !empty($PACMEC['fullData']['sku'])
              && isset($PACMEC['fullData']['name']) && !empty($PACMEC['fullData']['name'])
              && isset($PACMEC['fullData']['description']) && !empty($PACMEC['fullData']['description'])
              && isset($PACMEC['fullData']['description_full'])
              && isset($PACMEC['fullData']['unid']) && !empty($PACMEC['fullData']['unid'])
              && isset($PACMEC['fullData']['is_active']) && !empty($PACMEC['fullData']['is_active'])
              && isset($PACMEC['fullData']['available'])
              && isset($PACMEC['fullData']['price_normal'])
              && isset($PACMEC['fullData']['price_promo'])
              && isset($PACMEC['fullData']['observations'])
            ) {
              $PACMEC['fullData']['sku'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['sku']), FILTER_SANITIZE_URL));
              $PACMEC['fullData']['common_names'] = implode(',', explode(',', $PACMEC['fullData']['common_names']));
              if(!isset($PACMEC['fullData']['description_full_style'])) $PACMEC['fullData']['description_full_style'] = "";
              if($client->sku !== $PACMEC['fullData']['sku']) $client->sku = $PACMEC['fullData']['sku'];
              if($client->name !== $PACMEC['fullData']['name']) $client->name = $PACMEC['fullData']['name'];
              if($client->description !== $PACMEC['fullData']['description']) $client->description = $PACMEC['fullData']['description'];
              if($client->common_names !== $PACMEC['fullData']['common_names']) $client->common_names = $PACMEC['fullData']['common_names'];
              if($client->unid !== $PACMEC['fullData']['unid']) $client->unid = $PACMEC['fullData']['unid'];
              if($client->is_active !== $PACMEC['fullData']['is_active']) $client->is_active = $PACMEC['fullData']['is_active'];
              if($client->available !== $PACMEC['fullData']['available']) $client->available = $PACMEC['fullData']['available'];
              if($client->price_normal !== $PACMEC['fullData']['price_normal']) $client->price_normal = $PACMEC['fullData']['price_normal'];
              if($client->price_promo !== $PACMEC['fullData']['price_promo']) $client->price_promo = $PACMEC['fullData']['price_promo'];
              if($client->observations !== $PACMEC['fullData']['observations']) $client->observations = $PACMEC['fullData']['observations'];
              if($client->description_full !== $PACMEC['fullData']['description_full']) $client->description_full = $PACMEC['fullData']['description_full'];
              if($client->description_full_style !== $PACMEC['fullData']['description_full_style']) $client->description_full_style = $PACMEC['fullData']['description_full_style'];
              $client->created_by = \userID();
              $result_save = $client->create([
                "sku"
                , "name"
                , "description"
                , "description_full"
                , "description_full_style"
                , "common_names"
                , "unid"
                , "is_active"
                , "created_by"
                , "available"
                , "price_normal"
                , "price_promo"
                , "observations"
              ]);
              if($result_save == true){
                $form->setSucessMessage(
                  __a('save_success')
                  . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                );
                return true;
              } else {
                $form->setErrorMessage(__a('save_fail'));
                return false;
              }
            } else {
              $form->setErrorMessage(__a('form_invalid'));
              return false;
            }
            break;
          default:
            $form->setErrorMessage(__a($result_captcha));
            return false;
            break;
        }
        $form->setErrorMessage(__a('form_invalid'));
        return false;
      }),
    ]);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('names', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('client') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('sku', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('sku_ref') . ' <span class="required">*</span>'
      , ''
    , []);
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea('', [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "description",
      ], [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        , new \PACMEC\Form\Validation\MinLengthValidation(6)
      ])
      , __a('description') . ' <span class="required">*</span>'
      , ''
      , [
    ]);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('common_names', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"             => "pacmec-input pacmec-border pacmec-round-large",
        "data-role"         => "tagsinput",
      ])
      , __a('common_names') . ' <span class="required">*</span>'
      , ''
    , []);

    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('available', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('available') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $obj_unids = [""=>"Seleccione una opcion"];
    $obj_unids_k = [];
    foreach (type_options('measurement_units') as $option){
      $obj_unids[$option->code]   = $option->name;
      $obj_unids_k[] = $option->code;
    }
    $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('unid', $obj_unids, '', $obj_unids_k, ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
    , __a('unid')
    , ''
    , ['pacmec-col s12 m3']);
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_normal', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('price_normal') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('price_promo', '', 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class"     => "pacmec-input pacmec-border pacmec-round-large number_format",
      ])
      , __a('price_promo') . ' <span class="required">*</span>'
      , ''
      , ['pacmec-col s12 m3']
    );
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea('', [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "observations",
      ], [
      ])
      , __a('observations')
      , 'Estas observaciones solo serán visibles por el personal que gestiona las ordenes/clientos/servicios.'
      , [
    ]);
    $form->Code .= \PACMEC\Util\Html::clearfix();
    if(infosite('grapesjs_enable')==true) $form->Code .= "<br><div class=\"pacmec-col s12\" id=\"gjs\">{$description_full}<style>{$description_full_style}</style></div>";
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full, [
        'class'  => 'pacmec-input pacmec-border pacmec-round-large',
        "name"   => "description_full",
        "id"     => "html",
      ], [
        #new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        #, new \PACMEC\Form\Validation\MinLengthValidation(6)
      ])
      , __a('description_full') . ' <span class="required">*</span>'
      , ''
      , [
        ((infosite('grapesjs_enable')==true) ? "hidden" : "")
    ]);
    $form->addFieldWithLabel(new \PACMEC\Form\Textarea($description_full_style, [
        'class'=>'pacmec-input pacmec-border pacmec-round-large',
        "name" => "description_full_style",
        "id" => "css",
      ], [
      ])
      , __a('description_full_style')
      , ''
      , [
        ((infosite('grapesjs_enable')==true) ? "hidden" : "")
    ]);
    $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
    $form->Code .= "<div class=\"clearfix\"><br></div>";
    $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
      'id'=>"submit-{$form_slug}",
      'name'=>"submit-{$form_slug}",
      "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
    ]);
    if(isset($PACMEC['fullData']['redirect'])) $btn_submit->addHrefButton(__a('btn_cancel'), $PACMEC['fullData']['redirect'], ['pacmec-btn pacmec-gray pacmec-round-large']);
    $form->Code .= $btn_submit;
    $html .= $form;
    return $html;
  }
  else {
    $client = new \PACMEC\System\Client((object) ['id'=>$client_id]);
    if($client->isValid()){
      $html .= "<style>.bootstrap-tagsinput { width: 100%; }</style>";
      $description_full           = "";
      $description_full_style     = "";
      $form_slug         = "admin-client-edit-form-pacmec";
      $result_captcha    = \pacmec_captcha_check($form_slug);
      $url_redirect      = isset($PACMEC['fullData']['redirect']) ? $PACMEC['fullData']['redirect'] : infosite('siteurl').$PACMEC['path'];
      $form = new \PACMEC\Form\Form(
      ''
      , 'POST'
      , PACMEC\Form\FormType::Normal
      , 'Error:'
      , "OK"
      , ['class'=>'pacmec-container', 'id'=>$form_slug]);
      $form->setWidths(12,12);
      $form->setGlobalValidations([
        new \PACMEC\Form\Validation\LambdaValidation('', function () use ($PACMEC, $form_slug, $result_captcha, $form, $client, $url_redirect) {
          if(!isset($PACMEC['fullData']["adcopy_response"]) && ($result_captcha !== 'captcha_disabled')) return false;
          switch ($result_captcha) {
            case 'captcha_r_success':
            case 'captcha_disabled':
              if(
                isset($PACMEC['fullData']['sku']) && !empty($PACMEC['fullData']['sku'])
                && isset($PACMEC['fullData']['name']) && !empty($PACMEC['fullData']['name'])
                && isset($PACMEC['fullData']['description']) && !empty($PACMEC['fullData']['description'])
                && isset($PACMEC['fullData']['description_full'])
                && isset($PACMEC['fullData']['unid']) && !empty($PACMEC['fullData']['unid'])
                && isset($PACMEC['fullData']['is_active']) && !empty($PACMEC['fullData']['is_active'])
                && isset($PACMEC['fullData']['available'])
                && isset($PACMEC['fullData']['price_normal'])
                && isset($PACMEC['fullData']['price_promo'])
                && isset($PACMEC['fullData']['observations'])
              ) {
                $PACMEC['fullData']['sku'] = strtolower(filter_var(str_replace([' ','%20','  ', '\n'], ['-'], $PACMEC['fullData']['sku']), FILTER_SANITIZE_URL));
                $PACMEC['fullData']['common_names'] = implode(',', explode(',', $PACMEC['fullData']['common_names']));
                if(!isset($PACMEC['fullData']['description_full_style'])) $PACMEC['fullData']['description_full_style'] = "";
                if($client->sku !== $PACMEC['fullData']['sku']) $client->sku = $PACMEC['fullData']['sku'];
                if($client->name !== $PACMEC['fullData']['name']) $client->name = $PACMEC['fullData']['name'];
                if($client->description !== $PACMEC['fullData']['description']) $client->description = $PACMEC['fullData']['description'];
                if($client->common_names !== $PACMEC['fullData']['common_names']) $client->common_names = $PACMEC['fullData']['common_names'];
                if($client->unid !== $PACMEC['fullData']['unid']) $client->unid = $PACMEC['fullData']['unid'];
                if($client->is_active !== $PACMEC['fullData']['is_active']) $client->is_active = $PACMEC['fullData']['is_active'];
                if($client->available !== $PACMEC['fullData']['available']) $client->available = $PACMEC['fullData']['available'];
                if($client->price_normal !== $PACMEC['fullData']['price_normal']) $client->price_normal = $PACMEC['fullData']['price_normal'];
                if($client->price_promo !== $PACMEC['fullData']['price_promo']) $client->price_promo = (float) $PACMEC['fullData']['price_promo'];
                if($client->observations !== $PACMEC['fullData']['observations']) $client->observations = $PACMEC['fullData']['observations'];
                if($client->description_full !== $PACMEC['fullData']['description_full']) $client->description_full = $PACMEC['fullData']['description_full'];
                if($client->description_full_style !== $PACMEC['fullData']['description_full_style']) $client->description_full_style = $PACMEC['fullData']['description_full_style'];
                $client->created_by = \userID();

                $result_save = $client->save([
                  "sku"
                  , "name"
                  , "description"
                  , "description_full"
                  , "description_full_style"
                  , "common_names"
                  , "unid"
                  , "is_active"
                  , "created_by"
                  , "available"
                  , "price_normal"
                  , "price_promo"
                  , "observations"
                ]);
                if($result_save == true){
                  $form->setSucessMessage(
                    __a('save_success')
                    . "<meta http-equiv=\"refresh\" content=\"0;URL='{$url_redirect}'\" />"
                  );
                  return true;
                } else {
                  $form->setErrorMessage(__a('save_fail'));
                  return false;
                }
              }
              $form->setErrorMessage(__a('form_invalid'));
              return false;
              break;
            default:
              $form->setErrorMessage(__a($result_captcha));
              return false;
              break;
          }
          $form->setErrorMessage(__a('form_invalid'));
          return false;
        }),
      ]);
      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('names', $client->names, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('client') . ' <span class="required">*</span>'
        , ''
      , []);

      $obj_types_clients = [""=>"Seleccione una opcion"];
      $obj_types_clients_k = [];
      foreach (
        $GLOBALS['PACMEC']['DB']->FetchAllObject("SELECT * FROM `{$GLOBALS['PACMEC']['DB']->getTableName('clients_types')}`", [])
      as $option){
        $obj_types_clients[$option->id]   = $option->name;
        $obj_types_clients_k[] = $option->name;
      }
      $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('type', $obj_types_clients, $client->type, $obj_types_clients_k, ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('type')
      , ''
      , ['pacmec-col s12']);

      $obj_identification_type = [""=>"Seleccione una opcion"];
      $obj_identification_type_k = [];
      foreach (type_options('identifications') as $option){
        $obj_identification_type[$option->id]   = "{$option->code} - {$option->name}";
        $obj_identification_type_k[] = $option->name;
      }
      $form->addFieldWithLabel(\PACMEC\Form\Select::withNameAndOptions('identification_type', $obj_identification_type, $client->type, $obj_identification_type_k, ['class'=>'pacmec-input pacmec-border pacmec-round-large'])
      , __a('identification_type')
      , ''
      , ['pacmec-col s12']);

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('identification_number', $client->identification_number, 254, [
          new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
        ], [
          "class" => 'pacmec-input pacmec-border pacmec-round-large'
        ])
        , __a('identification_number') . ' <span class="required">*</span>'
        , ''
      , []);

      $obj_unids = [""=>"Seleccione una opcion"];
      $obj_unids_k = [];
      foreach (type_options('measurement_units') as $option){
        $obj_unids[$option->code]   = $option->name;
        $obj_unids_k[] = $option->code;
      }

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('phone', $client->phone, 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('phone') . ' <span class="required">*</span>'
      , ''
      , []);

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('mobile', $client->mobile, 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('mobile') . ' <span class="required">*</span>'
      , ''
      , []);

      $form->addFieldWithLabel(\PACMEC\Form\Text::withNameAndValue('email', $client->email, 254, [
        new \PACMEC\Form\Validation\RequiredValidation(__a('required_field'))
      ], [
        "class" => 'pacmec-input pacmec-border pacmec-round-large'
      ])
      , __a('email') . ' <span class="required">*</span>'
      , ''
      , []);

      $form->Code .= \PACMEC\Util\Html::clearfix();

      $form->Code .= \PACMEC\Util\Html::tag('div', "<br/>".\pacmec_captcha_widget_html("pacmec-captcha-".randString(11), $form_slug, 'custom-pacmec'), ['pacmec-content']);
      $form->Code .= "<div class=\"clearfix\"><br></div>";
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);
      $form->Code .= "<div class=\"clearfix\"><br></div>";
      $btn_submit = new \PACMEC\Form\Submit(__a('btn_save'), [
        'id'=>"submit-{$form_slug}",
        'name'=>"submit-{$form_slug}",
        "class" => 'pacmec-btn pacmec-teal pacmec-round-large'
      ]);
      $btn_submit->addHrefButton(__a('btn_cancel'), ((isset($PACMEC['fullData']['redirect'])) ? $PACMEC['fullData']['redirect'] : __url_s("/%admin_blog_slug%")), ['pacmec-btn pacmec-gray pacmec-round-large']);
      $form->Code .= $btn_submit;
      $html .= $form;
    } else {
      $html .= "Cliento no encontrado";
    }
  }
  return $html;
}
add_shortcode('pacmec-admin-clients-table', 'pacmec_admin_clients_table');

function pacmec_widget_comments($atts, $content="")
{
  global $PACMEC;
  $r = "";
  $args = shortcode_atts([
    'class' => ''
  ], $atts);
  if (infosite('comments_enabled')==true && $PACMEC['route']->comments_enabled):
    $items = "";
    foreach ($PACMEC['route']->comments as $comment):
      $items .= \PACMEC\Util\Html::tag('div',
        \PACMEC\Util\Html::tag('a',
          \PACMEC\Util\Html::tag('img', "", ['author-thumb'], ["src"=>"/.pacmec/themes/destry/assets/images/blog/thumb/1.jpg"], true)
        , ['author-thumb'], ["href"=>"#"])
        . \PACMEC\Util\Html::tag('div',
          \PACMEC\Util\Html::tag('p', $comment->comment, ['mb-1'])
          .\PACMEC\Util\Html::tag('div',
            \PACMEC\Util\Html::tag('span',
              \PACMEC\Util\Html::tag('a', \PACMEC\Util\Html::tag('strong', $comment->display_name, []), [], ["href"=>"#"])
            , ['author'])
          , ['comment-footer d-flex justify-content-between'])
        , ['comments-info'])
      , ['single-comment-wrap mb-10']);
    endforeach;
    $area = \PACMEC\Util\Html::tag('div',
      \PACMEC\Util\Html::tag('h3', count($PACMEC['route']->comments)." ".__a('reviews_or_comments').$items, ['title mb-6'])
    , ['comment-area-wrapper mt-5'], ["data-aos"=>"fade-up", "data-aos-delay"=>"400"]);
    $form = \PACMEC\Util\Html::tag('div',
      \PACMEC\Util\Html::tag('div', do_shortcode("[pacmec-comment-form][/pacmec-comment-form]"), ['comment-box'])
    , ['blog-comment-form-wrapper mt-10'], ["data-aos"=>"fade-up", "data-aos-delay"=>"400"]);
    $r .= $area.$form;
  endif;
  return $r;
}
add_shortcode('pacmec-widget-comments', 'pacmec_widget_comments');
