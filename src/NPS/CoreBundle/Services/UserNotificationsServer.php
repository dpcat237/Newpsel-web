<?php
namespace NPS\CoreBundle\Services;

class UserNotificationsServer extends AbstractEmailNotificationService
{
    public function sendEmailVerification()
    {
        /*$to      = 'dpcat237@gmail.com';
        $subject = 'the subject';
        $message = 'hello';
        $headers = 'From: newpsel@gmail.com' . "\r\n" .
            'Reply-To: newpsel@gmail.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();

        mail($to, $subject, $message, $headers);*/

        $viewData = array();

        $message = \Swift_Message::newInstance()
            ->setSubject("Subject test 2")
            ->setFrom('newpsel@gmail.com')
            ->setTo("dpcat237@gmail.com")
            ->setBody($this->getTemplating()->render('NPSFrontendBundle:Email:email_verification.html.twig', $viewData)
            )
            ->setContentType('text/html');
        //$this->getMailer()->send($message);

        $transporter = \Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
            ->setUsername('newpsel@gmail.com')
            ->setPassword('n#06p04e2013r#s');

        $mailer = \Swift_Mailer::newInstance($transporter);
        $mailer->send($message);


        echo 'tute: ok'; exit();
    }

    /**
     * Sends an email to a each shop about their new order
     *
     * @param Order $order
     */
    public function sendShopOrderConfirmation(Order $order)
    {
        //first, send email to each shop
        $shopLines = array();
        $shops = array();
        $lines = $order->getLines();
        //build an array of shops and another of orderlines discriminated by shop
        foreach ($lines as $line) {
            $shopId = $line->getProduct()->getShop()->getId();
            $shops[$shopId] = $line->getProduct()->getShop();
            if (empty($shopLines[$shopId][$line->getOrder()->getId()])) {
                $shopLines[$shopId][$line->getOrder()->getId()] = array();
            }
            $shopLines[$shopId][$line->getOrder()->getId()][] = $line;
        }

        //send 1 email to each shop with their lines
        foreach ($shops as $shopId => $shop) {
            //decide which locale to use
            $shopUser = $shop->getUsers()->first();
            $this->getTranslator()->setLocale($shopUser->getLanguage()->getIso());
            $translationVars = array('%order_id%' => $order->getId(), '%order_reference%' => $order->getReference());
            try {
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->getTranslator()->trans('order_received_title', $translationVars, 'emails'))
                    ->setFrom('admin@chicplace.com')
                    ->setTo($shopUser->getEmail())
                    ->setBody(
                        $this->getTemplating()->render(
                            'ChicplaceWebBundle:Email:orderreceived.html.twig',
                            array(
                                'is_reminder' => false,
                                'shop'  => $shop,
                                'order' => $order,
                                'lines' => $shopLines[$shopId][$order->getId()]
                            )
                        )
                    )
                    ->setContentType('text/html');
                $this->getMailer()->send($message);
            } catch (\Exception $e) {
                //mail could not be sent
                $message = \Swift_Message::newInstance()
                    ->setSubject('Exception sending email from Shop Notifications')
                    ->setFrom('admin@chicplace.com')
                    ->setTo('admin@chicplace.com')
                    ->setBody('Exception '.$e->getMessage().' thrown when sending mail to '.$shopUser->getEmail());
                $this->getMailer()->send($message);
            }
        }
    }

}