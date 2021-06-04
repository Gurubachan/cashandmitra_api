<!doctype html>
<html class="no-js" lang="en">

<!-- Mirrored from rockstheme.com/rocks/bultifore-preview/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 14 Feb 2021 08:12:40 GMT -->
<head>
		<meta charset="utf-8">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<title>CASHAND | An Initiative Towards Branchless Banking</title>
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<!-- favicon -->
		<link rel="shortcut icon" type="image/x-icon" href="{{asset('img/logo/favicon.ico')}}">

		<!-- all css here -->

		<!-- bootstrap v3.3.6 css -->
		<link rel="stylesheet" href="{{asset('css/bootstrap.css')}}">
		<!-- owl.carousel css -->
		<link rel="stylesheet" href="{{asset('css/owl.carousel.css')}}">
		<link rel="stylesheet" href="{{asset('css/owl.transitions.css')}}">
        <!-- Animate css -->
        <link rel="stylesheet" href="{{asset('css/animate.css')}}">
        <!-- Nice-select css -->
        <link rel="stylesheet" href="{{asset('css/nice-select.css')}}">
        <!-- meanmenu css -->
        <link rel="stylesheet" href="{{asset('css/meanmenu.css')}}">
		<!-- font-awesome css -->
		<link rel="stylesheet" href="{{asset('css/font-awesome.min.css')}}">
		<link rel="stylesheet" href="{{asset('css/themify-icons.css')}}">
		<link rel="stylesheet" href="{{asset('css/flaticon.css')}}">
		<!-- magnific css -->
        <link rel="stylesheet" href="{{asset('css/magnific.min.css')}}">
		<!-- style css -->
		<link rel="stylesheet" href="{{asset('css/style.css')}}">
		<!-- responsive css -->
		<link rel="stylesheet" href="{{asset('css/responsive.css')}}">

		<!-- modernizr css -->
		<script src="{{asset('js/vendor/modernizr-2.8.3.min.js')}}"></script>
        <style>

        </style>
	</head>
		<body>

		<!--[if lt IE 8]>
			<p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
		<![endif]-->

        <div id="preloader"></div>
        <!-- Start Header Area -->
        <header class="header-one">
            <!-- Start header top bar -->
            <div class="topbar-area">
                <div class="container">
                    <div class="row">
                        <div class=" col-md-6 col-sm-6 col-xs-12">
                            <div class="topbar-left">
                                <ul>
                                    <li><a href="#"><i class="fa fa-envelope"></i> customercare@cashand.in</a></li>
                                    <li><a href="#"><i class="fa fa-clock-o"></i> Live support</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="topbar-right">
								{{--<ul>
                                    <li><a href="#"><img src="img/icon/w1.png" alt="">ENG</a>
                                       <ul>
                                           <li><a href="#"><img src="img/icon/w2.png" alt="">DEU</a>
                                           <li><a href="#"><img src="img/icon/w3.png" alt="">ESP</a>
                                           <li><a href="#"><img src="img/icon/w4.png" alt="">FRA</a>
                                           <li><a href="#"><img src="img/icon/w5.png" alt="">KSA</a>
                                       </ul>
                                    </li>
                                    <li><a href="login.html"><img src="img/icon/login.png" alt="">Login</a>
                                </ul>--}}
							</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End header top bar -->
            <!-- Start header menu area -->
            <div id="sticker" class="header-area hidden-xs">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <div class="row">
                                <!-- logo start -->
                                <div class="col-md-2 col-sm-2 ">
                                    <div class="logo">
                                        <!-- Brand -->
                                        <a class="navbar-brand page-scroll" href="{{url('home')}}">
                                            <img src="{{asset('img/logo/logo.png')}}" alt="">
                                        </a>
                                    </div>
                                    <!-- logo end -->
                                </div>
                                <div class="col-md-10 col-sm-10">
                                    <div class="header-right-link">
                                        <!-- search option end -->
                                        <a class="s-menu" href="{{url('login')}}">Login</a>
                                    </div>
                                    <!-- mainmenu start -->
                                    <nav class="navbar navbar-default">
                                        <div class="collapse navbar-collapse" id="navbar-example">
                                            <div class="main-menu">
                                                <ul class="nav navbar-nav navbar-right">
                                                    <li><a class="pages" href="{{url('home')}}">Home</a>
                                                        <!-- <ul class="sub-menu">
                                                            <li><a href="index-2.html">Home 01</a></li>
                                                            <li><a href="index-3.html">Home 02</a></li>
                                                        </ul> -->
                                                    </li>
                                                    <li><a  class="pages" href="{{url('about')}}">About us</a>
                                                    <ul class="sub-menu">
                                                            <li><a href="{{url('team')}}">team</a></li>
                                                            <li><a href="{{url('faq')}}">FAQ</a></li>
                                                            <li><a href="{{url('pricing')}}">Pricing</a></li>
                                                            <li><a href="{{url('review')}}">Reviews</a></li>
                                                            <li><a href="{{url('terms')}}">Terms & Conditions</a></li>

                                                        </ul></li>
                                                    <li><a class="pages" href="{{url('services')}}">Services</a>
                                                        <ul class="sub-menu">
                                                            <li><a href="team.html">Branchless Banking</a></li>
                                                            <li><a href="faq.html">Digital Services</a></li>
                                                            <li><a href="pricing.html">Insurance</a></li>
                                                            <li><a href="review.html">Travel</a></li>
                                                            <li><a href="terms.html">Utility Bill Payment</a></li>
                                                            <li><a href="login.html">Payment Getway</a></li>
                                                            <li><a href="signup.html">Partener Service</a></li>
                                                        </ul>
                                                    </li>
                                                    <li><a class="pages" href="#">Partner Program</a>
                                                        <!-- <ul class="sub-menu">
                                                            <li><a href="a-dashboard.html">Dashboard</a></li>
                                                            <li><a href="a-send-money.html">Send Money</a></li>
                                                            <li><a href="a-request-money.html">Request Money</a></li>
                                                            <li><a href="a-withdraw-money.html">Withdraw Money</a></li>
                                                            <li><a href="a-deposite-money.html">Deposite Money</a></li>
                                                            <li><a href="a-currency-change.html">Currency Exchange</a></li>
                                                            <li><a href="a-add-bank.html">Bank Account</a></li>
                                                            <li><a href="a-card-number.html">Card Number</a></li>
                                                            <li><a href="a-transection-log.html">Transection Log</a></li>
                                                            <li><a href="a-setting-money.html">Notifications</a></li>
                                                        </ul> -->
                                                    </li>
                                                    <li><a class="pages" href="#">Blog</a>
                                                        <!-- <ul class="sub-menu">
                                                            <li><a href="blog.html">Blog grid</a></li>
                                                            <li><a href="blog-sidebar.html">Blog Sidebar</a></li>
                                                            <li><a href="blog-details.html">Blog Details</a></li>
                                                        </ul> -->
                                                    </li>
                                                    <li><a href="{{url('contact')}}">contacts</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </nav>
                                    <!-- mainmenu end -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End header menu area -->
            <!-- Start mobile menu area -->
            <div class="mobile-menu-area hidden-lg hidden-md hidden-sm">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mobile-menu">
                                <div class="logo">
                                    <a href="{{url('home')}}"><img src="{{asset('img/logo/logo.png')}}" alt="" /></a>
                                </div>
                                <nav id="dropdown">
                                    <ul>
                                        <li><a class="pages" href="{{url('home')}}">Home</a>
                                            {{--<ul class="sub-menu">
                                                <li><a href="index-2.html">Home 01</a></li>
                                                <li><a href="index-3.html">Home 02</a></li>
                                            </ul>--}}
                                        </li>
                                       {{-- <li><a href="about.html">About us</a></li>--}}
                                        <li><a class="pages" href="{{url('about')}}">About Us</a>
                                            <ul class="sub-menu">
                                                <li><a href="{{url('team')}}">team</a></li>
                                                <li><a href="{{url('faq')}}">FAQ</a></li>
                                                <li><a href="{{url('pricing')}}">Pricing</a></li>
                                                <li><a href="{{url('review')}}">Reviews</a></li>
                                                <li><a href="{{url('terms')}}">Terms & Conditions</a></li>
                                            </ul>
                                        </li>
                                        <li><a class="pages" href="{{url('services')}}">Services</a>
                                            <ul class="sub-menu">
                                                <li><a href="team.html">Branchless Banking</a></li>
                                                <li><a href="faq.html">Digital Services</a></li>
                                                <li><a href="pricing.html">Insurance</a></li>
                                                <li><a href="review.html">Travel</a></li>
                                                <li><a href="terms.html">Utility Bill Payment</a></li>
                                                <li><a href="login.html">Payment Getway</a></li>
                                                <li><a href="signup.html">Partener Service</a></li>
                                            </ul>
                                        </li>
                                        <li><a class="pages" href="#">Partner Program</a></li>
                                        <li><a class="pages" href="#">Blog</a>
                                        </li>
                                        <li><a href="{{url('contact')}}">contacts</a></li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End mobile menu area -->
        </header>
        <!--  End Header Area -->
         <!-- Start intro Area -->


		<!-- <div class="slide-area fix">
            <div class="display-table">
                <div class="display-table-cell">
					<div class="container">
						<div class="row">
                            <div class="slide-text-inner">
                                <div class="col-md-6 col-sm-12 col-xs-12">
                                    <div class="slide-content">
                                        <h2 class="title2">Move money in easy secure steps</h2>
                                        <p>Fast and easy you want to be more secure send and recives money sort time</p>
                                        <div class="layer-1-3">
                                            <a href="contact.html" class="ready-btn" >Get started</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-12 col-xs-12">
                                   <div class="money-send">
                                        <div class="calculator-inner">
                                            <div class="single-cal">
                                                <div class="inner-form">
                                                    <form action="#">
                                                        <label>You send</label>
                                                        <input type="number" class="form-input" placeholder="$1000">
                                                        <select>
                                                            <option value="position">USD</option>
                                                            <option value="position">EUR</option>
                                                            <option value="position">KSR</option>
                                                            <option value="position">INR</option>
                                                            <option value="position">BDT</option>
                                                        </select>
                                                    </form>
                                                </div>
                                                <div class="inner-form">
                                                    <form action="#">
                                                        <label>Recipient Gets</label>
                                                        <input type="number" class="form-input" placeholder="$5000">
                                                        <select>
                                                            <option value="position">USD</option>
                                                            <option value="position">EUR</option>
                                                            <option value="position">USD</option>
                                                            <option value="position">INR</option>
                                                            <option value="position">BDT</option>
                                                        </select>
                                                    </form>
                                                </div>
                                                <div class="inner-form-text">
                                                    <div class="rate-text">
                                                        <span> <strong>82.50</strong> Exchange rate</span>
                                                        <span> <strong>$5.50</strong> Transition fees</span>
                                                    </div>
                                                </div>
                                                <button class="cale-btn">Continue</button>
                                                <div class="terms-text">
                                                    <p>By clicking continue, I am agree with <a href="#">Terms & Policy</a></p>
                                                </div>
                                            </div>
                                        </div>
                                   </div>
                                </div>
                            </div>
						</div>
					</div>
				</div>
            </div>
		</div> -->


		<!-- End intro Area -->
       @yield('containt')
        <!-- Start footer area -->
        <footer class="footer-1">
            <div class="footer-area">
                <div class="container">
                    <div class="row">
                       <!-- Start column-->
                       <div class="col-md-4 col-sm-6 col-xs-12">
                            <div class="footer-content logo-footer">
                                <div class="footer-head">
                                    <div class="footer-logo">
                                    	<a class="footer-black-logo" href="#"><img src="img/logo/logo.png" alt=""></a>
                                    </div>
                                    <p style="color: black">
                                        Replacing a  maintains the amount of lines. When replacing a selection. help agencies to define their new business objectives and then create. Replacing a  maintains the amount of lines.
                                    </p>
                                    <div class="subs-feilds">
                                        <div class="suscribe-input">
                                            <input type="email" class="email form-control width-80" id="sus_email" placeholder="Type Email">
                                            <button type="submit" id="sus_submit" class="add-btn">Subscribe</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End column-->
                        <!-- Start column-->
                        <div class="col-md-2 col-sm-3 col-xs-12">
                            <div class="footer-content">
                                <div class="footer-head">
                                    <h4>Services</h4>
                                    <ul class="footer-list">
                                        <li><a href="#">Digital Payments</a></li>
                                        <li><a href="#">Banking Service</a></li>
                                        <li><a href="#">Utility Payment</a></li>
                                        <li><a href="#">Insurance</a></li>
                                        <li><a href="#">Travel </a></li>
                                        <li><a href="#">Partner Service </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- End column-->
                        <!-- Start column-->
                        <div class="col-md-2 col-sm-3 col-xs-12">
                            <div class="footer-content">
                                <div class="footer-head">
                                    <h4>Payments</h4>
                                    <ul class="footer-list">
                                        <li><a href="#">Send Money</a></li>
                                        <li><a href="#">Receive Money </a></li>
                                        <li><a href="#">Shopping</a></li>
                                        <li><a href="#">Online payment</a></li>
                                        <li><a href="#">pay a Friend </a></li>
                                        <li><a href="#">pay a bill </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- End column-->
                        <!-- Start column-->
                        <div class="col-md-2 hidden-sm col-xs-12">
                            <div class="footer-content">
                                <div class="footer-head">
                                    <h4>Company</h4>
                                    <ul class="footer-list">
                                        <li><a href="#">About us</a></li>
                                        <li><a href="#">Services </a></li>
                                        <li><a href="#">Events</a></li>
                                        <li><a href="#">Promotion</a></li>
                                        <li><a href="#">Transition</a></li>
                                        <li><a href="#">Social Media</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- End column-->
                        <!-- Start column-->
                        <div class="col-md-2 hidden-sm col-xs-12">
                             <div class="footer-content last-content">
                                <div class="footer-head">
                                    <h4>Support</h4>
                                    <ul class="footer-list">
                                        <li><a href="#">Customer Care</a></li>
                                        <li><a href="#">Live chat</a></li>
                                        <li><a href="#">Notification</a></li>
                                        <li><a href="#">Privacy</a></li>
                                        <li><a href="#">Terms & Condition</a></li>
                                        <li><a href="#">Contact us </a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <!-- End column-->
                    </div>
                </div>
            </div>
            <!-- Start footer bottom area -->
            <div class="footer-area-bottom">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <div class="copyright">
                                <p>
                                    Copyright © 2016 - 2021
                                    <a href="#">CASHAND</a> All Rights Reserved
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End footer bottom area -->
        </footer>
        <!-- End footer area -->


		<!-- all js here -->

		<!-- jquery latest version -->
		<script src="{{asset('js/vendor/jquery-1.12.4.min.js')}}"></script>
		<!-- bootstrap js -->
		<script src="{{asset('js/bootstrap.min.js')}}"></script>
		<!-- owl.carousel js -->
		<script src="{{asset('js/owl.carousel.min.js')}}"></script>
        <!-- stellar js -->
        <script src="{{asset('js/jquery.stellar.min.js')}}"></script>
		<!-- magnific js -->
        <script src="{{asset('js/magnific.min.js')}}"></script>
        <!-- Nice-select js -->
        <script src="{{asset('js/jquery.nice-select.min.js')}}"></script>
        <!-- wow js -->
        <script src="{{asset('js/wow.min.js')}}"></script>
        <!-- meanmenu js -->
        <script src="{{asset('js/jquery.meanmenu.js')}}"></script>
		<!-- Form validator js -->
		<script src="{{asset('js/form-validator.min.js')}}"></script>
		<!-- plugins js -->
		<script src="{{asset('js/plugins.js')}}"></script>
		<!-- main js -->
		<script src="{{asset('js/main.js')}}"></script>
	</body>

<!-- Mirrored from rockstheme.com/rocks/bultifore-preview/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 14 Feb 2021 08:14:33 GMT -->
</html>
