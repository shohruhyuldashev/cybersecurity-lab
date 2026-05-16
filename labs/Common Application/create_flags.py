import os

flags = {
    "flags/nginx/flag2.txt": "HTB{Dir3ct0ry_Brut3_F0rc3_M4st3r}",
    "flags/wp/flag3_comment.txt": "HTB{W0rdPr3ss_Us3r_Enum_D0n3}",
    "flags/wp/flag4_draft.txt": "HTB{XMLRPC_Brut3_F0rc3_Succ3ss}",
    "flags/wp/flag5_config.txt": "HTB{WP_C0nf1g_L34k3d}",
    "flags/wp/flag6_rce.txt": "HTB{W0rdPr3ss_Th3m3_RC3_W00t}",
    "flags/joomla/flag7_joomla.xml": "HTB{J00ml4_V3rsi0n_Enum3r4ti0n}",
    "flags/joomla/flag8_config.txt": "HTB{J00ml4_Gl0b4l_C0nf1g_4cc3ss}",
    "flags/joomla/flag9_rce.txt": "HTB{J00ml4_T3mpl4t3_RC3_0wn3d}",
    "flags/drupal/flag10_changelog.txt": "HTB{Drup4l_Ch4ng3l0g_F0und}",
    "flags/drupal/flag11_rce.txt": "HTB{Drup4lg3dd0n_RC3_C0mpl3t3}",
    "flags/tomcat/flag12_banner.txt": "HTB{T0mc4t_AJP_8009_Enum}",
    "flags/tomcat/flag13_manager.txt": "HTB{T0mc4t_M4n4g3r_Brut3_F0rc3}",
    "flags/tomcat/flag14_war.txt": "HTB{W4R_P4yl04d_G3n3r4ti0n_M4st3r}",
    "flags/tomcat/flag15_rce.txt": "HTB{T0mc4t_M4n4g3r_RC3_W1n}",
    "flags/jenkins/flag16_build.txt": "HTB{J3nk1ns_Un4uth_Bui1d_H1st0ry}",
    "flags/jenkins/flag17_rce.txt": "HTB{J3nk1ns_Scr1pt_C0ns0l3_RC3}",
    "flags/jenkins/flag18_win.txt": "HTB{J3nk1ns_W1nd0ws_RC3_S1mul4t10n}",
    "flags/splunk/flag19_dash.txt": "HTB{Splunk_D4shb04rd_L34k}",
    "flags/splunk/flag20_rce.txt": "HTB{Splunk_4pp_RC3_0wn3d}",
    "flags/cgi/flag21_cgi.txt": "HTB{CG1_Scr1pt_D1sc0v3r3d}",
    "flags/cgi/flag22_shellshock.txt": "HTB{Sh3llsh0ck_RC3_C0mpl3t3}",
    "flags/gitlab/flag23_api.txt": "HTB{G1tl4b_4P1_Sn1pp3t_L34k}",
    "flags/gitlab/flag24_mass.txt": "HTB{M4ss_4ss1gnm3nt_Vuln_F0und}",
    "flags/gitlab/flag25_master.txt": "HTB{M4st3r_S3rv3r_SSH_K3y_0bt4in3d}"
}

for path, content in flags.items():
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w') as f:
        f.write(content + "\n")
print("Flags generated successfully.")
