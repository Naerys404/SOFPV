<?php

namespace App\Controller;

use FFMpeg\FFMpeg;
use App\Entity\Image;
use App\Entity\Likes;
use App\Entity\Video;
use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Service\Pagination;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;
use App\Repository\LikesRepository;
use App\Repository\ArticleRepository;
use FFMpeg\Filters\Video\ResizeFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class BlogController extends AbstractController
{
    //page principale du blog (liste des articles)
    #[Route('/blog/{page<\d+>?1}', name: 'blog')]
    public function index(Pagination $paginationService, $page)
    {
        // $articles = $articleRepo->findAllArticlesByDate();

        $paginationService->setEntityClass(Article::class)
                        ->setPage($page)
                        ->setLimit(10)
                        ->setOrder('DESC')
                        
        ;

        return $this->render('blog/index.html.twig', [
           'title'=>'Blog',
           'pagination'=>$paginationService
        ]);
    }
    
    //ajout d'un article (avec possibilité d'intégrer une vidéo et/ou des images)
    #[Route('/blog/add', name:'article_add')]
    #[IsGranted("ROLE_USER")]
    public function add(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response{

        $user = $this->getUser();
        $article = new Article();
        $video = new Video();
     
        $form = $this->createForm(ArticleType::class, $article);   
        $form->handleRequest($request);  
      
        if($form->isSubmitted() && $form->isValid()){
            
            $article = $form->getData();

            //On garde la mise en place des sauts de ligne avec nl2br()
            $articleContent = nl2br($article->getContent());
            
           //GESTION IMAGE 
           $images = $form->get('images')->getData();
            if($article->getImages()){

                $index = 0;
                
                 foreach($images as $image){

                    $caption = $form['images'][$index]['caption']->getData(); // indexage pour récupérer les données de chaque entrée
                    $source = $form['images'][$index]['source']->getData(); 
                   

                    $originalName = pathinfo($source->getClientOriginalName(), PATHINFO_FILENAME);
                    $sluggedName = $slugger->slug($originalName);
                    $newName = $sluggedName.'-'.uniqid().'.'.$source->guessExtension();

                        try {
                            $source->move($this->getParameter('upload_image'), $newName); // ok

                        } catch(FileException $e) {
                            dd($e->getMessage());                    
                        }
                        $newImage = new Image();
                        $image->setSource($newName)
                             ->setCaption($caption)
                             ->setArticle($article); 
                        $article->addImage($newImage);

                        $manager->persist($newImage);
                        $index++;   
                }       
            }      

            //GESTION VIDEO

            //on teste si l'user à voulu mettre une vidéo :
            //récupération du champ source via le formulaire imbriqué VideoType
            $videoSource = $form->get('video')->get('source')->getData(); 
            $ffmpeg = $this->initFfmpeg();


            //si il y a une vidéo on la traite avec FFmpeg
            if($videoSource){
                $originalName = $videoSource->getClientOriginalName(); //récupère le nom  
                $upVideo = $ffmpeg->open($videoSource->getRealPath()); //récupère le chemin pour traiter avec ffmpeg
                $title = $form->get('video')->get('title')->getData();

                $sluggedName = $slugger->slug($originalName);
                $newName = $sluggedName.'-'.uniqId().'.mp4'; // nouveau nom + extension voulue
                $thumbName = $sluggedName.'-'.uniqId().'.png';// même principe pour la vignette générée

                //géneration de la video via ffmpeg, redimensionnement + synchro
                $upVideo
                    ->filters()
                    ->resize(new Dimension(1920, 1080), ResizeFilter::RESIZEMODE_INSET)
                    ->synchronize(); 
              
                $upVideo->frame(TimeCode::fromSeconds(5))
                        ->save($this->getParameter('upload_thumb').'/'.$thumbName); //sauvegarde et deplacement de la vignette

                $video->setThumbnail($thumbName);

                //récupération du temps de la video
                $duration = $this->transformTime($upVideo->getFormat()->get('duration'));
                $video->setDuration($duration);

                //sauvegarde avec le codex X264
                $upVideo->save(new X264('libmp3lame', 'libx264'), $this->getParameter('upload_video').'/'.$newName);
                  
                $video->setSource($newName);
                $video->setTitle($title);
                $video->setUser($user);

                $manager->persist($video);

                //association de la vidéo avec l'article qui le possède
                $article->setVideo($video);
                
            }

            // dd($article);

            $article->setContent($articleContent);
            $article->setAuthor($user);
            $manager->persist($article); 

            $manager->flush();

            $this->addFlash('success', 'Article publié !');

            return $this->redirectToRoute('account_myprofile');
            
        }

        

        return $this->render('blog/article/add.html.twig', [
            'title'=>'Publier un article',
            'form'=>$form->createView()

            ]);
        }
  

    //GESTION VIDEO - Initialisation + calcul de la durée
    
      //Initialisation de FFMPEG pour l'intégration des vidéos
      private function initFfmpeg(){
        return $ffmpeg = FFMpeg::create(array(
            'ffmpeg.binaries'=> 'C:/Users/Naerys/Desktop/Projet examen/SoFPV/ffmpeg/ffmpeg.exe',
            'ffprobe.binaries' => 'C:/Users/Naerys/Desktop/Projet examen/SoFPV/ffmpeg/ffprobe.exe',
            'timeout'=> 3600, 
            'ffmpeg.threads' => 12
        ));
    }
    
    //Gestion de la durée + gestion de l'affichage de la vidéo
    private function transformTime($second){
        $hours = floor($second/36000);
        $mins = floor(($second-($hours*3600))/60);
        $secs = floor($second % 60);
        $hours = ($hours<1)? "" : $hours."h";
        $mins = ($mins<10)? "0".$mins.":" : $mins.":";
        $secs = ($secs<10) ? "0".$secs : $secs;

        $duration = $hours.$mins.$secs;
        return $duration;
    }


    //Visualisation de l'article + autres articles du même auteur en excluant l'article actuel (Utilisateur connecté uniquement : comparaison avec l'auteur pour ajout ou non d'une vue + possible ajout de commentaire)
    #[Route('/blog/show/{slug}', name:'article_show')]
    #[IsGranted('ROLE_USER')]
    public function show(Article $article, ArticleRepository $articleRepo, EntityManagerInterface $manager, Request $request){

        $author = $article->getAuthor();
        $title = $article->getTitle();
        $video = $article->getVideo();

        //gestion des commentaires
        $comment = new Comment();
        $formComment = $this->createForm(CommentType::class, $comment);
        $formComment->handleRequest($request);


        if($formComment->isSubmitted() && $formComment->isValid()){
            //On récupère le commentaire et on applique la méthode php nl2br() pour conserver les sauts de ligne
            $nlbrContent = nl2br($comment->getContent());

            $comment->setAuthor($this->getUser())
                    ->setArticle($article)
                    ->setContent($nlbrContent);

            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('article_show', ['slug'=>$article->getSlug()]);
        }
        

        //on ajoute une vue à l'article si le viewer n'est pas l'auteur de l'article
        if($this->getUser()!= $author){
            $article->setViews($article->getViews() + 1 );
            $manager->persist($article);
            $manager->flush();
        }

        //articles associés à l'auteur
        $articles = $articleRepo->findOtherArticlesByAuthor($author->getId(), $article);

        return $this->render('blog/article/show.html.twig', [
            'article'=>$article,
            'articles'=>$articles,
            'video'=> $video,
            'title'=> $title.' - '.$author,
            'form'=>$formComment->createView()
        ]);
    }


    #[Route('/blog/{id}/like', options: ['expose' => true] , name:'article_like')]
    public function like(Article $article, EntityManagerInterface $manager, LikesRepository $likesRepository){

        //si on récupère un user connecté
        if($this->getUser()){

            $user = $this->getUser();

            if($user != $article->getAuthor()){
            $isLiked = $likesRepository->getLikeByUserAndArticle($user, $article);

            //si c'est déjà liké et qu'on rappuie sur "like", on enlève le like
            if($isLiked){
                $manager->remove($isLiked);
            //sinon on créé un nouveau Like qui va s'associer à l'user et à l'article    
            } else {
                $like = new Likes();
                $like->setUser($user)
                    ->setArticle($article);
                $manager->persist($like);
            }

            $manager->flush();
            return new JsonResponse('success', 200);

        } else {
            return new JsonResponse(['erreur', 'Vous ne pouvez pas liker vos articles.']);
        }
        //si l'user tente de like sans être connecté, on retourne une réponse en JSON    
        } else {
            return new JsonResponse(['erreur', 'Attention, vous devez être connecté pour pouvoir liker un article.']);
        }

            return $this->render('blog/index.html.twig', [
                'title'=>'Blog',
             ]);
         
           

    }

    
}

