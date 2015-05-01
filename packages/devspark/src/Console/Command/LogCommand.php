<?php
namespace Devspark\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogCommand
 * @package Devspark\Console\Command
 */
class LogCommand extends Command {

    private $AWS_SNS_CLIENT = null;

    /**
     * Configuring supported commands and their help information.
     */
    protected function configure()
    {
        $conf = \Devspark\Di\Container::getInstance()->get('configuration');

        $this
            ->setName('log:sns')
            ->setDescription('Log a message with SNS')
            ->addArgument(
                'topic',
                InputArgument::REQUIRED,
                'The topic alias name: <comment>['.implode('|',$conf['LOGGER-CLI']['topic']).']</comment>'
            )
            ->addArgument(
                'level',
                InputArgument::REQUIRED,
                'The message level: <comment>['.implode('|',$conf['LOGGER-CLI']['level']).']</comment>'
            )
            ->addArgument(
                'message',
                InputArgument::REQUIRED,
                'The message to log'
            );
    }

    /**
     * Command execution method
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $conf = \Devspark\Di\Container::getInstance()->get('configuration');

        //Checking message data
        $message = trim($input->getArgument('message'));
        if (empty($message)) {
            $output->writeln("<error>The message cannot be empty</error>");
            exit;
        }

        //Checking topic
        $topic = $input->getArgument('topic');
        if (!in_array($topic, $conf['LOGGER-CLI']['topic'])) {
            $output->writeln("<error>Invalid topic</error>");
            exit;
        }

        //Checking level
        $level = $input->getArgument('level');
        if (!in_array($level, $conf['LOGGER-CLI']['level'])) {
            $output->writeln("<error>Invalid level</error>");
            exit;
        }

        //Creating the SNS client
        $this->AWS_SNS_CLIENT =  \Aws\Sns\SnsClient::factory(array(
                'credentials' => array(
                    'key'    => $conf['AWS']['credentials.key'],
                    'secret' => $conf['AWS']['credentials.secret'],
                ),
                'region'  => $conf['AWS']['region']
            )
        );

        //Building the whole message
        $__message = array();
        $__message['content'] = json_decode($message,true);
        $_aws_info = "DEMO";//$this->getMetaDataInfo();
        if( is_array($__message['content']) ){
            $__message['content']['_aws-info'] = json_decode($_aws_info);
        } else {
            $__message['_aws-info'] = json_decode($_aws_info);
        }

        /**
         * SNS message:
         *      default: to send different suscriptors.
         *      email: to send only for email suscriptors
         *      sms: to send only for sms suscriptors
         */
        $_msg = array(
                "default"=> json_encode($__message),
                "email"=> json_encode($__message),
                "sms"=> "A new SNS log has been added to $topic"
        );

        $topicArn = $conf['SNS-TOPICS'][$topic];
        $this->logSNSMessage(json_encode($_msg), $topicArn);

        $output->writeln('<info>The message has been sent</info>');
    }

    /**
     * Retrieves meta-data info from EC2 instance
     *
     * @return \Guzzle\Http\EntityBodyInterface|string
     */
    private function getMetaDataInfo(){
        $_aws_info = '';
        $ip = gethostbyname(gethostname());

        $client = new \Guzzle\Http\Client('http://'.$ip);
        $request = $client->get('/latest/meta-data/iam/info');
        $response = $request->send();

        if( $response->getStatusCode() == 200 ){
            $_aws_info = $response->getBody(true);
        } else {
            $_aws_info = 'Error retrieving meta-data info for EC2 instance: '.$ip;
        }

        return $_aws_info;
    }

    /*
    private function createSNSTopic($topicName){

        $result = $this->AWS_SNS_CLIENT->createTopic(array(
            // Name is required
            'Name' => 'racing-swf-pipeline',
        ));

        return $result->get('TopicArn');

    }*/

    /**
     * Log message in SNS
     *
     * @param json $message
     * @param string $topicArn
     * @return \Guzzle\Service\Resource\Model
     */
    private function logSNSMessage($message,$topicArn){
        $result = $this->AWS_SNS_CLIENT->publish(array(
            'TopicArn' => $topicArn,
            'Message' => $message,
            'Subject' => 'Log message',
            'MessageStructure' => 'json',
        ));

        return $result;
    }


}
