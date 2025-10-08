# WP Safe htaccess Manager
<br/>It is a WordPress plugin that allows you to edit htaccess online
<br/>Author: Roberto Aleman
<br/>Web: [ventics.com](https://ventics.com/wp-safe-htaccess-manager/)

<span dir="auto">WP Safe HTAccess Manager: Official Documentation</span>
<h2><span dir="auto">Introduction: User Needs and Plugin Purpose</span></h2>
<span dir="auto">Web security and performance optimization often require modifying the .php file </span><b><code>.htaccess</code></b><span dir="auto">, a powerful, central configuration file on Apache servers. However, this file is notoriously </span><b><span dir="auto">sensitive to syntax errors</span></b><span dir="auto"> . A single misplaced character can result in a </span><b><span dir="auto">500 Internal Server Error</span></b><span dir="auto"> , rendering your entire website inaccessible—sometimes even to you.</span>

<span dir="auto">Users need a </span><b><span dir="auto">fast, secure, and auditable</span></b><span dir="auto"> way to implement critical security rules (such as blocking XML-RPC or adding security headers) without the constant fear of breaking their site.</span>
<h3><span dir="auto">Purpose of the Plugin</span></h3>
<b><span dir="auto">WP Safe HTAccess Manager</span></b><span dir="auto"> solves this problem by providing a simple, controlled interface within WordPress. Its main purpose is to:</span>
<ol start="1">
 	<li><b><span dir="auto">Ensure Stability:</span></b><span dir="auto"> Each change is subjected to a simulated </span><b><span dir="auto">"Atomic Stability Test</span></b><span dir="auto"> ." If the write fails (due to permission or potential syntax errors), the plugin doesn't save the configuration to the WordPress database and alerts you, acting as a </span><b><span dir="auto">500 error prevention</span></b><span dir="auto"> mechanism .</span></li>
 	<li><b><span dir="auto">Offer Key Rules:</span></b><span dir="auto"> Provides a set of </span><b><span dir="auto">predefined templates</span></b><span dir="auto"> for the most common and recommended security rules.</span></li>
 	<li><b><span dir="auto">Maintain Auditability:</span></b><span dir="auto"> All applied rules are embedded with </span><b><span dir="auto">delimiters, timestamps, and attribution</span></b><span dir="auto"> , facilitating future debugging and change tracking.</span></li>
</ol>
<h2><span dir="auto">Installation and User Guide</span></h2>
<h3><span dir="auto">1. Installation</span></h3>
<img class="alignnone size-full wp-image-14334" src="https://ventics.com/wp-content/uploads/2025/10/ventics.com_WP-Safe-Htaccess-Manager-1.jpg" alt="WP Safe Htaccess Manager" width="1274" height="629" />
<ol start="1">
 	<li><b><span dir="auto">Upload File:</span></b><span dir="auto"> You need to place the plugin code in a folder called wp-safe-htaccess-manager , zip it up, and upload it to your WordPress installation. <span dir="auto">(or plugin folder, if packaged) to the </span><code>wp-content/plugins/</code><span dir="auto">.</span></li>
 	<li><b><span dir="auto">Activate:</span></b><span dir="auto"> Go to </span><b><span dir="auto">Plugins</span></b><span dir="auto"> in your WordPress dashboard and click </span><b><span dir="auto">Activate</span></b><span dir="auto"> for </span><i><span dir="auto">WP Safe HTAccess Manager</span></i><span dir="auto"> .</span></li>
 	<li><b><span dir="auto">Access:</span></b><span dir="auto"> The settings menu will appear under </span><b><span dir="auto">Settings </span></b><span class="math-inline"><span class="katex"><span class="katex-html" aria-hidden="true"></span></span></span> <b><span dir="auto">WPSHtaccess Manager</span></b></li>
</ol>
<h3><span dir="auto">2. Use (Applying Safety Rules)</span></h3>
<span dir="auto">The admin panel is divided into clear sections:</span>
<h4><span dir="auto">1. Suggested Security Rules</span></h4>
<span dir="auto">This section contains common security rule templates.</span>
<ul>
 	<li><b><span dir="auto">To Enable:</span></b><span dir="auto"> Check the </span><i><span dir="auto">box</span></i><span dir="auto"> next to the rule name (example: “Protect wp-config.php”).</span></li>
 	<li><b><span dir="auto">To Disable:</span></b><span dir="auto"> Uncheck the </span><i><span dir="auto">checkbox</span></i><span dir="auto"> .</span></li>
 	<li><b><span dir="auto">The Active/Inactive</span></b><span dir="auto"> state reflects the configuration that will be saved.</span></li>
</ul>
<h4><span dir="auto">2. Custom Rules</span></h4>
<span dir="auto">If you need to add custom Apache directives not covered by the templates, you can paste them into this text field.</span>

   <img class="size-full wp-image-14335" src="https://ventics.com/wp-content/uploads/2025/10/ventics.com_Custom-Rules-.jpg" alt="Custom Rules" width="1289" height="532" />
   
<ul>
 	<li><b><span dir="auto">Important:</span></b><span dir="auto"> Enter only valid Apache directives. Remember that these will also be tested by the Atomic Test!</span></li>
</ul>
<h4><span dir="auto">3. Execute Atomic Test</span></h4>
<span dir="auto">This is the crucial step:</span>
<ol start="1">
 	<li><span dir="auto">Once you've selected your rules and added custom code, click the main button: </span><b><span dir="auto">Execute Atomic Test and Apply Changes to .htaccess</span></b><span dir="auto"> .</span></li>
 	<li><b><span dir="auto">Successful Result:</span></b><span dir="auto"> If the operation is successful (writing to the file works), you will receive a message saying “Changes applied successfully! Atomic Test passed.” and your new rules will appear in the file </span><code>.htaccess</code><span dir="auto">with their timestamps.</span></li>
 	<li><b><span dir="auto">Failed Result:</span></b><span dir="auto"> If the operation fails (usually due to file permissions), you will receive an error message and </span><b><span dir="auto">your file </span><code>.htaccess</code><span dir="auto">will not be modified</span></b><span dir="auto"> , preventing a 500 error.</span></li>
</ol>
<h2><span dir="auto">Technical Documentation</span></h2>
<h3><span dir="auto">Execution Flow and Atomic Test</span></h3>
<span dir="auto">The plugin uses the </span><b><span dir="auto">POST-Redirect-GET (PRG)</span></b><span dir="auto"> principle to handle form submissions and executes the save logic in the </span><i><span dir="auto">hook</span></i> <code>admin_init</code><span dir="auto"> .</span>
<ol start="1">
 	<li><b><span dir="auto">Form Handling ( </span><code>handle_form_submission</code><span dir="auto">)</span></b><span dir="auto"> :</span>
<ul>
 	<li><span dir="auto">Check the user capacity ( </span><code>manage_options</code><span dir="auto">).</span></li>
 	<li><b><span dir="auto">Nonce Check:</span></b><span dir="auto"> Performs strict nonce verification ( </span><code>shield_apply_changes</code><span dir="auto">) to protect against CSRF attacks. Failure to do so will stop the process and display the error "Security check failed."</span></li>
 	<li><span dir="auto">Collects the database configuration.</span></li>
</ul>
</li>
 	<li><b><span dir="auto">Content Generation ( </span><code>generate_rules_block_content</code><span dir="auto">)</span></b><span dir="auto"> :</span>
<ul>
 	<li><span dir="auto">This function takes the active rules and custom code.</span></li>
 	<li><span dir="auto">Create an audit line: </span><code># Added by WP Safe HTAccess Manager on [Fecha y Hora]</code><span dir="auto">.</span></li>
 	<li><span dir="auto">Compiles all active rules into a single block of text, including the audit trail for each rule.</span></li>
</ul>
</li>
 	<li><b><span dir="auto">Secure Writing (The Simulated «Atomic Test»)</span></b><span dir="auto"> :</span>
<ul>
 	<li><span dir="auto">The plugin uses the core WordPress function: </span><code>insert_with_markers( $htaccess_path, $marker, $rules_to_insert )</code><span dir="auto">.</span></li>
 	<li><span dir="auto">This function finds the marker ( </span><code># BEGIN WP SHIELD RULES</code><span dir="auto">) and </span><b><span dir="auto">replaces</span></b><span dir="auto"> all the delimited content with the new rules.</span></li>
 	<li><b><span dir="auto">The Atomic Test is simulated:</span></b><span dir="auto"> If </span><code>insert_with_markers</code><span dir="auto">it returns , the stability <i>test</i></span><code>TRUE</code><span dir="auto"> is considered passed, and the configuration is saved to the database ( ). If it returns (usually due to file permissions), the <i>test</i> fails, the database is not modified, and the user receives the error message.</span><i></i><code>update_option</code><code>FALSE</code><i></i></li>
</ul>
</li>
</ol>
&nbsp;
<h3><span dir="auto">Structure of Markers in</span><code>.htaccess</code></h3>
<span dir="auto">Plugin rules are always inserted between the following delimiters, ensuring they do not interfere with WordPress rules or other plugin rules (such as caching):</span>
<br/># BEGIN WP SHIELD RULES
<br/># — Rule: Block XML-RPC —
<br/># Added by WP Safe HTAccess Manager on 2025-10-01 20:25:28.
<br/>&lt;Files xmlrpc.php&gt;
<br/>Order Deny,Allow
<br/>Deny from all
<br/>&lt;/Files&gt;
<br/># — Rule: Custom HTAccess Code —
<br/># Added by WP Safe HTAccess Manager on 2025-10-01 20:25:28.
<br/># (Tu código personalizado aquí)
<br/>END WP SHIELD RULES 
<h3><span dir="auto">WordPress Options Used</span></h3>
<span dir="auto">The plugin saves its state and configuration in the table </span><code>wp_options</code><span dir="auto">:</span>
<div class="horizontal-scroll-wrapper">
<div class="table-block-component">
<div class="table-block has-export-button">
<div class="table-content not-end-of-paragraph" data-hveid="0" data-ved="0CAAQ3ecQahgKEwin6anTkYSQAxUAAAAAHQAAAAAQuAc">
<table>
<thead>
<tr>
<td>Clave (Option Key)</td>
<td><span dir="auto">Use</span></td>
<td><span dir="auto">Data Type</span></td>
</tr>
</thead>
<tbody>
<tr>
<td><code>wp_safe_htaccess_rules</code></td>
<td><span dir="auto">Associative array </span><code>['rule_key'] =&gt; 1 o 0</code><span dir="auto">for predefined rules.</span></td>
<td><code>array</code></td>
</tr>
<tr>
<td><code>wp_safe_htaccess_custom_code</code></td>
<td><span dir="auto">The custom HTAccess code entered by the user.</span></td>
<td><code>string</code></td>
</tr>
<tr>
<td><code>wp_shield_messages</code></td>
<td><span dir="auto">Used </span><code>transient</code><span dir="auto">to display success or error messages after redirection.</span></td>
<td><code>transient</code></td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
Disclaimer. This software is provided as is, you are responsible for using it in your WordPress installation, but the author is not responsible for any misuse that the user may give it and that could potentially take your site offline.
